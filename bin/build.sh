#!/usr/bin/env bash

which lessc > /dev/null
if [ $? -ne 0 ] ; then
  echo -e "\nInstalling LESS..."
  sudo npm install -g less less-plugin-clean-css sane || exit $?
fi

if [ "$1" == "--watch" ]; then
  echo -e "\nPress Ctrl-C to stop.\n"
  sane 'echo "Building..."; lessc -s resources/assets/less/login.less --source-map=public/dist/login.map --clean-css="--s1" public/dist/login.css && echo "Done.\n"' resources/assets/less --glob='**/*.less'
else
echo -e "Note: when developing, you can compile automatically using the --watch flag.\n\nBuilding..."
lessc -s resources/assets/less/login.less --source-map=public/dist/login.map --clean-css="--s1" public/dist/login.css && echo -e "Done.\n"
fi
