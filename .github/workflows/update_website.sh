!/bin/bash

# Define the local and remote branch names
local_branch="main"
remote_branch="origin/main"

# Pull the latest changes from the remote repository
git pull

# Get the latest commit hash
latest_commit=$(git rev-parse HEAD)

# Verify if the local branch is up to date with the remote branch
if [ "$(git rev-parse $local_branch)" != "$(git rev-parse $remote_branch)" ]; then
    echo "Local branch is not up to date. Pushing changes..."

    # Push the local changes to the remote branch
    git push origin $local_branch

    echo "Changes pushed successfully!"
else
    echo "Local branch is up to date."
fi

# Deploy the updates to your website (replace with your deployment command)
echo "Deploying Modula-lite test updates to the test website..."
# Your deployment command goes here
echo "Modula-lite test update deployed successfully!"

# This file needs to be made executable with  chmod +x update_website.sh
# To run the script :  ./update_website.sh
# Note: We need to update the local branch and remote branch
