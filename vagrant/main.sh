#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo ">>> Update repo"
apt-get -y --quiet update
apt-get -y --quiet install curl vim
