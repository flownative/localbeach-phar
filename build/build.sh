#!/usr/bin/env bash

export APP_VERSION=${CI_COMMIT_REF_NAME}

mkdir -p ~/.ssh && ssh-keyscan -t rsa -p 2222 git.flownative.com >> ~/.ssh/known_hosts && echo "$CI_DEPLOYMENT_PRIVATE_KEY" > ~/.ssh/deploymentkey_rsa && chmod 0600 ~/.ssh/deploymentkey_rsa
echo "$CI_BUILDER_SERVICE_ACCOUNT" > ~/google-service-account.json
gcloud auth activate-service-account --key-file ~/google-service-account.json

curl -SL "https://github.com/box-project/box2/releases/download/2.7.5/box-2.7.5.phar" -o build/box.phar && chmod 0775 build/box.phar

composer --no-dev -o -v --no-progress --ignore-platform-reqs install

envsubst < build/constants.php.tpl > constants.php
php -d phar.readonly=0 build/box.phar build
gsutil cp beach.phar gs://cli-tool.beach.flownative.cloud/beach-${APP_VERSION}.phar
