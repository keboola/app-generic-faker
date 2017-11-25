#!/bin/bash
set -e

# deploy to Quay public repository
docker login -u="$QUAY_USERNAME" -p="$QUAY_PASSWORD" quay.io
docker tag keboola/generic-extractor quay.io/keboola/generic-extractor:${TRAVIS_TAG}
docker tag keboola/generic-extractor quay.io/keboola/generic-extractor:latest
docker images
docker push quay.io/keboola/generic-extractor:${TRAVIS_TAG}
docker push quay.io/keboola/generic-extractor:latest

# Obtain the application repository and log in
# docker pull quay.io/keboola/developer-portal-cli-v2:latest
# export REPOSITORY=`docker run --rm  \
#     -e KBC_DEVELOPERPORTAL_USERNAME \
#     -e KBC_DEVELOPERPORTAL_PASSWORD \
#     quay.io/keboola/developer-portal-cli-v2:latest \
#     ecr:get-repository ${KBC_DEVELOPERPORTAL_VENDOR} ${KBC_DEVELOPERPORTAL_APP}`
# eval $(docker run --rm \
#     -e KBC_DEVELOPERPORTAL_USERNAME \
#     -e KBC_DEVELOPERPORTAL_PASSWORD \
#     quay.io/keboola/developer-portal-cli-v2:latest \
#     ecr:get-login ${KBC_DEVELOPERPORTAL_VENDOR} ${KBC_DEVELOPERPORTAL_APP})

# # Push to the repository
# docker tag ${KBC_APP_REPOSITORY}:latest ${REPOSITORY}:${TRAVIS_TAG}
# docker tag ${KBC_APP_REPOSITORY}:latest ${REPOSITORY}:latest
# docker push ${REPOSITORY}:${TRAVIS_TAG}
# docker push ${REPOSITORY}:latest

# # Deploy to KBC -> update the tag in Keboola Developer Portal (needs $KBC_DEVELOPERPORTAL_VENDOR & $KBC_DEVELOPERPORTAL_APP)
# docker run --rm \
#     -e KBC_DEVELOPERPORTAL_USERNAME \
#     -e KBC_DEVELOPERPORTAL_PASSWORD \
#     quay.io/keboola/developer-portal-cli-v2:latest \
#     update-app-repository ${KBC_DEVELOPERPORTAL_VENDOR} ${KBC_DEVELOPERPORTAL_APP} ${TRAVIS_TAG} ecr ${REPOSITORY}
