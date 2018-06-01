# Changelog

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

## 2.1.0

Added support for `many_many through` relations.


## 2.0.0

First release for SilverStripe 4.1

#### What happened to `has_many` support?

Support for `has_many` relations has been dropped, since it can lead to a very bad user experience if a file can only be added to a single page.
Imagine a user added an image to `Page A`, then adds the same image via _Add from files_ to `Page B`.
The file would then be removed from `Page A`, without any warning or explanation, which is bad UX.
