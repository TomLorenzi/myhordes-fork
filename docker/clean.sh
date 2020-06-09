#!/bin/bash

docker rm -vf $(docker ps -aqf name=myhordes_) # Remove all containers with name starting by myhodes_

# docker rmi -f $(docker images -aq) # Remove all images
# docker system prune -a --volumes # Remove all stopped containers, unused volumes