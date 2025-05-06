# SQL and Github Commands

# SQL
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE child_1_1;
TRUNCATE TABLE child_1;

SET FOREIGN_KEY_CHECKS = 1;


# Github

```git
git clone https://github.com/your-repo.git
git status
git commit -m "Updated code"


git checkout xcd_branch
git pull origin xcd_branch
# Do your work, make changes...
git add .
git commit -m "Finished feature X"
git push origin xcd_branch
git checkout main
git pull origin main
git merge xcd_branch
git push origin main