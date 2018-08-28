#!/usr/bin/env bash

export APP_VERSION=${CI_COMMIT_REF_NAME}

echo "$CI_BUILDER_SERVICE_ACCOUNT" > ~/google-service-account.json
gcloud auth activate-service-account --key-file ~/google-service-account.json
gsutil cp gs://cli-tool.beach.flownative.cloud/beach-${APP_VERSION}.phar gs://cli-tool.beach.flownative.cloud/beach.phar
