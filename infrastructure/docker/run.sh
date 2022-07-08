#!/bin/sh

APP_VERSION=develop
VERSION=alpha

docker build --no-cache --build-arg APP_VERSION=${APP_VERSION} --tag=danskernesdigitalebibliotek/smart-search-service:${VERSION} --file="smart-search-service/Dockerfile" smart-search-service
docker build --no-cache --build-arg VERSION=${VERSION} --tag=danskernesdigitalebibliotek/smart-search-service-nginx:${VERSION} --file="nginx/Dockerfile" nginx

docker push danskernesdigitalebibliotek/smart-search-service:${VERSION}
docker push danskernesdigitalebibliotek/smart-search-service-nginx:${VERSION}
