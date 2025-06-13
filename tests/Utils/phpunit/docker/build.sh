#!/bin/bash

docker compose build --build-arg GID=$(id --group) --build-arg UID=$(id --user)
