workflow "Deploy" {
  resolves = ["WordPress Plugin Deploy"]
  on = "push"
}

action "Composer Production" {
  uses = "pxgamer/composer-action@master"
  args = "install --no-dev --optimize-autoloader"
}

action "Tag" {
  uses = "actions/bin/filter@master"
  args = "tag"
}

action "WordPress Plugin Deploy" {
  needs = ["Tag", "Composer Production"]
  uses = "10up/actions-wordpress/dotorg-plugin-deploy@master"
  secrets = ["SVN_PASSWORD", "SVN_USERNAME"]
}
