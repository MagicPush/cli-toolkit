ARG PHP_VERSION='8.3'

FROM php:$PHP_VERSION-cli-alpine

# Define the arguments needed below only after `FROM` statement is executed.
# Otherwise those arguments will not be accessible from here.
ARG GID='1000'
ARG UID='1000'

# Add a non-root user (ideally - with the same UID and GID as the host).
# Some tests rely on the fact that some files are not readable (for `root` all files are always readable).
RUN addgroup -g $GID -S phpunit
RUN adduser -u $UID -G phpunit -DHS phpunit
USER phpunit
