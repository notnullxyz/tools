#!/bin/bash
# THIS WILL DESTROY ALL your containers, images, volumes, networks...
# Do not use this, unless you're starting over.
# Stop all running containers

echo "Stopping all running containers..."
docker stop $(docker ps -aq)

# Remove all containers
echo "Removing all containers..."
docker rm $(docker ps -aq)

# Remove all images
echo "Removing all images..."
docker rmi $(docker images -q) --force

# Remove all volumes
echo "Removing all volumes..."
docker volume prune --force

# Remove all networks (except the default ones)
echo "Removing all networks..."
docker network prune --force

# Remove all unused data
echo "Cleaning up unused data..."
docker system prune --all --force --volumes

echo "Docker environment cleaned up successfully!"
