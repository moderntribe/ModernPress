# GitHub Actions

We use GitHub Actions for many automated tasks. Some actions run on push, some run when creating a release and others 
might only run when selecting them in the UI (workflow_dispatch). You can learn more about GitHub Actions through the 
documentation located at [docs.github.com/en/actions](https://docs.github.com/en/actions). All of these actions are a starting point for your project 
and you should be adjusting/adding/removing actions to best fit your project needs.

## Deployments

### Dokku Deployment Workflows

The [Dokku](https://dokku.com/) deployments are for internal qa and development testing. These include the dokku-dev.yml, 
dokku-review-app.yml, and pipeline-dokku.yml workflows. To use these deployments you need to update the `app_name` 
from `moose-dev` to the unique name for your projects environment and setup the environment in our private 
dokku-ansible repo to accept the connection.

## Static Analysis and Testing

There are a series of workflows that must pass in order to allow a pull request to be merged.  These are highlighted in 
the `workflows.yml` file and include the coding standards, static analysis, and linting.

