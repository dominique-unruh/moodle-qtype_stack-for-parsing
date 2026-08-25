# Demo service image for the etests stack-parser.
#
# Same Maxima + STACK setup as etests' docker/stack-parser/Dockerfile, but:
#   - COPY this repo in instead of git-cloning it (we already ARE the repo), and
#   - run a long-lived PHP web server instead of one-shot `bash /parse.sh`.
#
# Build from the REPO ROOT (context must include api/ and etests-service/):
#   docker build -f etests-service/Dockerfile -t etests-stack-parser:demo .
#
# NOTE: micromamba pulls linux-64 (amd64) Maxima. This is the amd64-only piece.
# On Apple Silicon, don't emulate it locally — run this container on an amd64
# host/server and point the client at that host (see README).

FROM docker.io/library/ubuntu:noble-20260810
LABEL org.opencontainers.image.description="etests stack-parser demo service"
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        git build-essential ca-certificates curl bzip2 \
        php-cli php-mbstring php-xml php-curl composer && \
    rm -rf /var/lib/apt/lists/*

# Maxima 5.44.0 from conda-forge (ECL backend). The apt maxima uses the gcl
# Lisp image, whose fixed-address unexec image segfaults under container ASLR.
ENV MAMBA_ROOT_PREFIX=/opt/micromamba
ENV PATH=/opt/micromamba/envs/maxima/bin:$PATH
RUN curl -Ls https://micro.mamba.pm/api/micromamba/linux-64/latest | tar -xj -C /usr/local bin/micromamba && \
    micromamba create -y -n maxima -c conda-forge maxima=5.44.0 && \
    micromamba clean -a -y

COPY . /moodle-qtype_stack
RUN cd /moodle-qtype_stack/api && composer install --no-interaction --no-progress

# One-time: create maximalocal.mac + build the optimized Maxima image, and
# smoke-test that "x" parses. `disable_functions=locale_lookup` forces the
# userland locale_lookup() stub (otherwise Maxima parsing does strange
# localization things).
RUN cd /moodle-qtype_stack/api/public && \
    php -d disable_functions=locale_lookup init.php

EXPOSE 8080
CMD ["php", "-d", "disable_functions=locale_lookup", \
     "-S", "0.0.0.0:8080", \
     "/moodle-qtype_stack/etests-service/service-router.php"]
