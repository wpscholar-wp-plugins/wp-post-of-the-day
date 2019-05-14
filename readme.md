# WP Post of the Day

## Description
The **WP Post of the Day** plugin allows you to display a new WordPress post every day.

https://wordpress.org/plugins/wp-post-of-the-day/

## Contributors

### Pull Requests
All pull requests are welcome.  This plugin is relatively simple, so I'll probably be selective when it comes to features.

### SVN Access
If you have been granted access to SVN, this section details the processes for reliably checking out the code and committing your changes.

#### Prerequisites
- Install Node.js
- Run `npm install -g gulp`
- Run `npm install` from the project root

#### Checkout
- Run `gulp checkout` from the project root

#### Check In
- Be sure that all version numbers in the code and readme have been updated.  Add changelog and upgrade notice entries.
- Tag the new version in Git
- Run `gulp` from the project root.
- Run `svn st | grep ^? | sed '\''s/?    //'\'' | xargs svn add && vn st | grep ^! | sed '\''s/!    //'\'' | xargs svn rm` to add and remove items from the SVN directory.
- Run `svn cp trunk tags/{version}` from the SVN root directory.
- Run `svn ci -m "{commit message}"` from the SVN root to commit changes.
