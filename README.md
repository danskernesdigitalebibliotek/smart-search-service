# Smart Search Service

This is a simple service that provides data file for the CMS system with
information needed by the `ting_smart_search` module.

It's a simple Symfony site which uses two console commands to parse CSV
information from KPI index into to output files that are publicised.

The project provides docker images and helm charts to deploy the setup to
kubernetes.

## Installation

It assumes that the cluster is installed with cover-service shared-config
helm charts to ensure storage class (if not the storage class should be
set to available class with helm `--set app.storage.class=`).

Prepare namespace

```yaml
kubectl create namespace smart-search
kubectl label namespaces/smart-search networking/namespace=smart-search
kubens smart-search
```

Install the service.

```yaml
helm upgrade --install smart-search infrastructure/smart-search-service/ --set ingress.domain=smartsearch.dandigbib.org
```

## Development Setup

A `docker-compose.yml` file with a PHP 7.4 image is included in this project.
To install the dependencies you can run

```shell
docker compose up -d
docker compose exec phpfpm composer install
```

### Unit Testing

A PhpUnit setup is included in this library. To run the unit tests:

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/simple-phpunit
```

### Psalm static analysis

We are using [Psalm](https://psalm.dev/) for static analysis. To run
psalm do

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/psalm
```

### Check Coding Standard

The following command let you test that the code follows
the coding standard for the project.

* PHP files (PHP-CS-Fixer)

    ```shell
    docker compose exec phpfpm composer coding-standards-check
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    docker run -v ${PWD}:/app itkdev/yarn:latest install
    docker run -v ${PWD}:/app itkdev/yarn:latest check-coding-standards
    ```

### Apply Coding Standards

To attempt to automatically fix coding style

* PHP files (PHP-CS-Fixer)

    ```sh
    docker compose exec phpfpm composer apply-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    docker run -v ${PWD}:/app itkdev/yarn:14 install
    docker run -v ${PWD}:/app itkdev/yarn:14 coding-standards-apply
    ```

## CI

Github Actions are used to run the test suite and code style checks on all PR's.

If you wish to test against the jobs locally you can install [act](https://github.com/nektos/act).
Then do:

```sh
act -P ubuntu-latest=shivammathur/node:latest pull_request
```

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/itk-dev/openid-connect/tags).

## License

This project is licensed under the AGPL-3.0 License - see the
[LICENSE.md](LICENSE.md) file for details
