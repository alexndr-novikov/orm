# Cycle ORM
[![Latest Stable Version](https://poser.pugx.org/cycle/orm/version)](https://packagist.org/packages/cycle/orm)
[![Build Status](https://travis-ci.org/cycle/orm.svg?branch=master)](https://travis-ci.org/cycle/orm)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/orm/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cycle/orm/?branch=master)
[![Codecov](https://codecov.io/gh/cycle/orm/graph/badge.svg)](https://codecov.io/gh/cycle/orm)
<a href="https://discord.gg/TFeEmCs"><img src="https://img.shields.io/badge/discord-chat-magenta.svg"></a>

Cycle is PHP DataMapper ORM and Data Modelling engine designed to safely work in classic and daemonized PHP applications (like [RoadRunner](https://github.com/spiral/roadrunner)). The ORM provides flexible configuration options to model datasets and support dynamic mapping schema at runtime. Engine can work with plain PHP objects, support annotation declarations and proxies via extensions.

<p align="center">
	<a href="https://github.com/cycle/docs"><b>Documentation</b></a> | <a href="https://github.com/cycle/docs/issues/3">Comparison with Eloquent and Doctrine</a>
</p>

Features:
---------
- ORM with has-one, has-many, many-thought-many and polymorphic relations
- Plain Old PHP Objects or [same entity type for multiple repositories](tests/ORM/Classless)
- eager and lazy loading, query builder with multiple fetch strategies
- embedded entities, lazy/eager loaded embedded partials
- runtime configuration with/without code-generation
- column-to-field mapping, single table inheritance, value objects support
- custom persist strategies, dirty state, safe entity map
- works with directed graphs and cyclic graphs using command chains
- designed to work in long-running applications, immutable service core, reconnects
- supports MySQL, MariaDB, PostgresSQL, SQLServer, SQLite
- schema scaffolding and migrations
- supports global query constrains, UUIDs as PK, soft deletes, auto timestamps
- disposable UoW, custom column types, FKs to non primary columns
- use with or without annotations, proxy classes and auto-migrations 
- compatible with Doctrine Collections, Doctrine Annotations and Zend Hydrator

Extensions:
---------
| Component | Current Status        
| ---       | ---
cycle/schema-builder | [![Latest Stable Version](https://poser.pugx.org/cycle/schema-builder/version)](https://packagist.org/packages/cycle/schema-builder) [![Build Status](https://travis-ci.org/cycle/schema-builder.svg?branch=master)](https://travis-ci.org/cycle/schema-builder) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/schema-builder/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cycle/schema-builder/?branch=master) [![Codecov](https://codecov.io/gh/cycle/schema-builder/graph/badge.svg)](https://codecov.io/gh/cycle/schema-builder)
cycle/annotated | [![Latest Stable Version](https://poser.pugx.org/cycle/annotated/version)](https://packagist.org/packages/cycle/annotated) [![Build Status](https://travis-ci.org/cycle/annotated.svg?branch=master)](https://travis-ci.org/cycle/annotated) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/annotated/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cycle/annotated/?branch=master) [![Codecov](https://codecov.io/gh/cycle/annotated/graph/badge.svg)](https://codecov.io/gh/cycle/annotated)
cycle/proxy-factory | [![Latest Stable Version](https://poser.pugx.org/cycle/proxy-factory/version)](https://packagist.org/packages/cycle/proxy-factory) [![Build Status](https://travis-ci.org/cycle/proxy-factory.svg?branch=master)](https://travis-ci.org/cycle/proxy-factory) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/proxy-factory/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cycle/proxy-factory/?branch=master) [![Codecov](https://codecov.io/gh/cycle/proxy-factory/graph/badge.svg)](https://codecov.io/gh/cycle/proxy-factory)
cycle/migrations | [![Latest Stable Version](https://poser.pugx.org/cycle/migrations/version)](https://packagist.org/packages/cycle/migrations) [![Build Status](https://travis-ci.org/cycle/migrations.svg?branch=master)](https://travis-ci.org/cycle/migrations) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/migrations/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cycle/migrations/?branch=master) [![Codecov](https://codecov.io/gh/cycle/migrations/graph/badge.svg)](https://codecov.io/gh/cycle/migrations)

License:
--------
The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by [Spiral Scout](https://spiralscout.com).

