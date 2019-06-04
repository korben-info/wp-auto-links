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
  uses = "10up/actions-wordpress/dotorg-plugin-deploy@4f0a053cb997f281b62963122fc0a5de18fc1aa9"
  secrets = [
    "SVN_PASSWORD",
    "SVN_USERNAME",
    "GITHUB_TOKEN",
  ]
}
