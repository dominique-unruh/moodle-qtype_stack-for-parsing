# moodle-qtype_stack — `for-etests-parser`

A fork of [STACK](https://github.com/maths/moodle-qtype_stack) used by **etests** for one job:
**parse student math input into a structured Maxima expression tree**, and expose that as a
small HTTP service. etests' `StackParser` sends an expression, STACK validates it exactly as it
would in a real question, and returns the parsed form as a JSON tree (not LaTeX).

This document describes everything on the `for-etests-parser` branch relative to `master`:
first the STACK/Maxima patches (committed on the branch), then the etests service layer
(added on top; may be uncommitted in your working tree).

---

## 1. STACK / Maxima patches (committed on the branch)

### `stack/maxima/stackmaxima.mac` — JSON-tree output (the core mechanism)
- Adds `expr_to_json(expr)`: recursively renders a Maxima expression as a nested array —
  `["operation", head, arg, …]`, `["symbol", s]`, `["integer", n]`, `["atom", s]`, `["float", s]`.
- Overrides `_CS2l` so STACK's "displayed" channel returns `string(expr_to_json(v))` instead of
  `stack_disp(v, "")` (LaTeX). This is what lets etests read a **structured** parse rather than
  scraping LaTeX. The result arrives wrapped as `\[ <json-tree> \]`.
- Hotfix: comments out `box(ex) := ex;` — recent Maxima added `box` as a builtin, which clashed.

### `stack/utils.class.php` — don't lose debug info
- Makes `$debuginfo` a global (was a per-instance `protected`), so debug/error text survives across
  the API call and can be surfaced on failure.

### STACK-as-a-library / API glue
- `api/config.php` — simulates the Moodle globals STACK expects (`$CFG`, versions, `castimeout=20`,
  `platform`, Maxima command, display mode `api`, etc.), so STACK runs outside Moodle.
- `api/public/parseexpression.php` — the original **one-shot** parse entry point: reads
  `$WORKDIR/question.xml` + `$WORKDIR/expression.txt`, validates the `ans1` input, writes
  `$WORKDIR/result.txt` (the pseudo-LaTeX JSON tree) and `$WORKDIR/errors.txt`.
- `api/public/init.php` — build-time init: `create_maximalocal()` + smoke-test that `"x"` parses.
- `api/public/question.xml`, `api/public/expression.txt` — sample inputs.
- `api/question/type/stack/stack/maxima` — symlink into the stack maxima dir.
- `.gitignore` — minor.

Notable branch commits: JSON-tree patch, "Fixing incompatibility with current Maxima" (the `box`
hotfix), CAS parse timeout raised to 20 s, and error-reporting improvements.

---

## 2. etests service layer (added on top)

Turns the one-shot `parseexpression.php` (a container per parse) into a **long-lived HTTP daemon**,
and adds a fast Maxima variant. These files sit alongside the STACK code:

| File | Role |
|------|------|
| `api/public/parseservice.php` | HTTP `/parse` endpoint — the daemon version of `parseexpression.php`: reads the request body, returns JSON `{result, errors}`. |
| `etests-service/service-router.php` | Router for PHP's built-in server: `/health` + `/parse`. |
| `etests-service/Dockerfile` | Plain image: conda-forge (ECL) Maxima, subprocess per parse (~520 ms). |
| `etests-service/Dockerfile.optimised` | Fast image: source-built **SBCL** Maxima + STACK's saved optimised image (~85 ms). |
| `api/public/build_optimage.php` | Build-time step that bakes the STACK optimised Maxima image. |
| `api/config.php` (edit) | `MAXIMA_OPTIMISED=1` selects `linux-optimised` + `maximacommandopt`; unset ⇒ plain platform. |
| `etests-service/smoke-test.sh` | `curl` the endpoints with the sample question. |

The Compose file lives in the **etests** repo at `services/docker-compose.yml` (the orchestration
layer for all etests services), not in this fork.

### API

`GET /health` → `{"status":"ok"}`

`POST /parse`
```json
{ "expression": "1/sqrt(2)*(ket(00)+ket(01))", "questionXml": "<quiz>...</quiz>" }
```
→
```json
{ "result": "\\[ [\"operation\", ...] \\]", "errors": null }
```
`result` is STACK's `$state->contentsdisplayed` verbatim (the `\[ <json-tree> \]` from the `_CS2l`
patch); `errors` is the validation error string or `null`. The caller builds `questionXml` exactly
as etests' `StackParser.parseFuture` does (a one-input STACK quiz via `MoodleStack`).

### Run

```bash
# from the etests repo's services/ dir
docker compose up --build
./stack-parser/etests-service/smoke-test.sh        # against http://localhost:<published port>
```
First build of the optimised image is slow (source-builds Maxima); later runs reuse the layer.

### Performance

| Image | Maxima | Per parse (warm) |
|-------|--------|------------------|
| `Dockerfile` (plain) | conda ECL, reloads `stackmaxima.mac` every start | ~520 ms |
| `Dockerfile.optimised` | source-built **SBCL** Maxima started from STACK's saved image | ~85 ms |

The plain image spends ~350 ms/parse loading `stackmaxima.mac` + libraries on every Maxima start.
The optimised image bakes those into a saved Lisp image (`stack/maxima_opt_auto`) at build time
(`build_optimage.php` → `create_auto_maxima_image()`), so Maxima starts pre-loaded.

**Why SBCL:** STACK can only save an optimised image on GCL/SBCL/CLISP — **not ECL** (conda-forge's
backend), and **not GCL** (its unexec image segfaults under container ASLR). So the optimised
Dockerfile source-builds Maxima against SBCL. A **goemaxima** server (`$CFG->maximacommandserver`)
is a further step — a warm Maxima *pool* as a separate service — worthwhile only when many frontends
share one pool.

### Gotchas

- `parseservice.php`/`build_optimage.php` define `function locale_lookup(){ return "en-US"; }` and
  run with `php -d disable_functions=locale_lookup`. Both are required — otherwise Maxima does
  strange localization things during parsing.
- STACK's `MoodleEmulation.php` does a relative `require '../config.php'`, so the server must run
  with cwd `api/public` (`service-router.php` `chdir()`s there).
- amd64 only (Maxima). On Apple Silicon, run the container on an amd64 host and point the client at
  it — only the URL changes.

---

## etests integration

In the etests repo, `StackParser.parseFuture` POSTs `{expression, questionXml}` to
`${stackparser.url}/parse` (typed via sttp + circe) and reads back `{result, errors}` — equal to the
old `result.txt` / `errors.txt`. If `stackparser.url` is unset it falls back to a one-shot Docker
container. The `result` wire value equals `contentsdisplayed`, so `StackParser`'s JSON-tree parsing
(`parseArray` → `maximaToStackMath`) is unchanged.
