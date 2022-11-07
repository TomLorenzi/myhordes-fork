#!/bin/bash
if [ -d "./public" ]; then

  : > "./public/humans.txt"
  printf "/* ALL CONTRIBUTORS */\n" >> "./public/humans.txt"
  others="0"
  while read contributor; do
    name=$( echo "$contributor" | grep -Po '\d+\s+\K.*?(?=\s<.*?>)' )
    contributions=$( echo "$contributor" | grep -Po '\d+(?=\s+.*?\s<.*?>)' )
    if [[ $name == "root" ]] || [ -z "$name" ]; then
      others=$((others+contributions))
    else
      printf "Contributor: $name\nContributions: $contributions\n\n" >> "./public/humans.txt"
    fi
  done <<< "$(git shortlog -sen HEAD)"
  if [ "$others" -gt "0" ]; then
    printf "Contributor: [ANONYMOUS]\nContributions: $others\n\n" >> "./public/humans.txt"
  fi


  last_version=""
  while read version; do
    others="0"
    if [ -z "$last_version" ]; then
      contributors=$(git shortlog -sen "$version" )
    else
      contributors=$(git shortlog -sen "$last_version..$version" )
    fi
    printf "/* CONTRIBUTORS FOR $version */\n" >> "./public/humans.txt"
    echo "$contributors" | while read contributor; do
      name=$( echo "$contributor" | grep -Po '\d+\s+\K.*?(?=\s<.*?>)' )
      contributions=$( echo "$contributor" | grep -Po '\d+(?=\s+.*?\s<.*?>)' )
      if [[ $name == "root" ]] || [ -z "$name" ]; then
        others=$((others+contributions))
      else
        printf "Contributor: $name\nContributions: $contributions\n\n" >> "./public/humans.txt"
      fi
    done
    if [ "$others" -gt "0" ]; then
      printf "Contributor: [ANONYMOUS]\nContributions: $others\n\n" >> "./public/humans.txt"
    fi
    last_version="$version"
  done <<< "$(git tag -l "v*" --sort "committerdate")"
  echo "Done!"
else
   echo "Could not find public directory."
   exit 2
fi

