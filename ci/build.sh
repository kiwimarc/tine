function login() {
  docker login "${REGISTRY}" --username "${REGISTRY_USER}" --password "${REGISTRY_PASSWORD}"
}

function build_and_push() {
  NAME=$1

  docker build \
    --target "${NAME}" \
    --tag "${REGISTRY}/${NAME}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
    --file "ci/dockerimage/Dockerfile" \
    --build-arg "BUILDKIT_INLINE_CACHE=1" \
    --build-arg "PHP_IMAGE=php" \
    --build-arg "PHP_IMAGE_TAG=${PHP_IMAGE_TAG}" \
    --build-arg "BASE_IMAGE=${REGISTRY}/base-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
    --build-arg "DEPENDENCY_IMAGE=${REGISTRY}/dependency-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
    --build-arg "SOURCE_IMAGE=${REGISTRY}/source-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
    --build-arg "BUILD_IMAGE=${REGISTRY}/build-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
    --build-arg "BUILT_IMAGE=${REGISTRY}/built-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" \
    --build-arg NPM_INSTALL_COMMAND="${NPM_INSTALL_COMMAND}" \
    --build-arg "NODE_TLS_REJECT_UNAUTHORIZED=0" \
    --build-arg "CUSTOM_APP_VENDOR=${CUSTOM_APP_VENDOR}" \
    --build-arg "CUSTOM_APP_NAME=${CUSTOM_APP_NAME}" \
    --build-arg "CUSTOM_APP_GIT_URL=${CUSTOM_APP_GIT_URL}" \
    --build-arg "CUSTOM_APP_VERSION=${CUSTOM_APP_VERSION}" \
    .

  echo "docker: built ${NAME} image"

  # use --quiet, when docker v 20.03 is available
  docker push "${REGISTRY}/${NAME}-commit:${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}"
}

function tag_major_as_commit_image() {
    NAME=$1

    tag_image "${REGISTRY}" "${NAME}" "${MAJOR_COMMIT_REF_NAME}-${PHP_IMAGE_TAG}" "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}"
}

function tag_commit_as_branch_image() {
    NAME=$1

    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" "${REGISTRY}" "${NAME}" "${CI_COMMIT_REF_NAME}-${PHP_IMAGE_TAG}"
}

function tag_commit_as_gitlab_image() {
    NAME=$1

    docker login -u "${CI_REGISTRY_USER}" -p "${CI_REGISTRY_PASSWORD}" "${CI_REGISTRY}"
    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" "${CI_REGISTRY}/tine20/tine20" "$NAME" "${CI_COMMIT_REF_NAME}-${PHP_IMAGE_TAG}"
}

function tag_commit_as_dockerhub_image() {
    NAME=$1
    DOCKERHUB_NAME=$2

    docker login -u "${DOCKERHUB_USER}" -p "${DOCKERHUB_TOKEN}" "docker.io"
    tag_image "${REGISTRY}" "${NAME}-commit" "${CI_PIPELINE_ID}-${PHP_IMAGE_TAG}" "docker.io/tine20" "${DOCKERHUB_NAME}" "${DOCKERHUB_TAG}"
}

function tag_image() {
  FROM_REG=$1
  FROM_NAME=$2
  FROM_TAG=$(echo $3 | sed sI/I-Ig)
  DEST_REG=$4
  DEST_NAME=$5
  DEST_TAG=$(echo $6 | sed sI/I-Ig)

  FROM_IMAGE="${FROM_REG}/${FROM_NAME}:${FROM_TAG}"
  DESTINATION_IMAGE="${DEST_REG}/${DEST_NAME}:${DEST_TAG}"

  docker pull "${FROM_IMAGE}"
  docker tag "${FROM_IMAGE}" "${DESTINATION_IMAGE}"
  docker push "${DESTINATION_IMAGE}"
}