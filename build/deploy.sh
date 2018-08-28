#!/usr/bin/env bash

export APP_VERSION=${CI_COMMIT_REF_NAME}

mkdir -p ~/.ssh && ssh-keyscan -t rsa -p 2222 git.flownative.com >> ~/.ssh/known_hosts && echo "$CI_DEPLOYMENT_PRIVATE_KEY" > ~/.ssh/deploymentkey_rsa && chmod 0600 ~/.ssh/deploymentkey_rsa
echo "$CI_BUILDER_SERVICE_ACCOUNT" > ~/google-service-account.json
gcloud auth activate-service-account --key-file ~/google-service-account.json
gsutil cp gs://cli-tool.beach.flownative.cloud/beach-${APP_VERSION}.phar gs://cli-tool.beach.flownative.cloud/beach.phar

export SHA256_HASH=$(shasum --algorithm 256 beach.phar | awk '{print $1}')

git clone git@github.com:flownative/homebrew-flownative.git
envsubst < build/beach-cli.rb.tpl > homebrew-flownative/Formula/beach-cli.rb
cd homebrew-flownative
git commit -a -m "Update beach.phar to ${APP_VERSION}"
git push
