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
