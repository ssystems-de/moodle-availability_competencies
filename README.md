moodle-availability_competencies
================================

[![Moodle Plugin CI](https://github.com/ssystems-de/moodle-availability_competencies/actions/workflows/moodle-plugin-ci.yml/badge.svg?branch=MOODLE_501_STABLE)](https://github.com/ssystems-de/moodle-availability_competencies/actions?query=workflow%3A%22Moodle+Plugin+CI%22+branch%3AMOODLE_501_STABLE)

Moodle availability condition that restricts access to activities and course sections until a learner has achieved global proficiency in a selected course competency.


Requirements
------------

This plugin requires Moodle 5.1+


Motivation for this plugin
--------------------------

Moodle's competency system tracks learner achievement across courses, but restrict access has historically lacked a native competency condition. Workarounds (e.g. chaining activity completion) only work within a single course. This plugin enables competency-based learning
paths—including cross-course prerequisites.


Installation
------------

Install the plugin like any other plugin to folder
/availability/condition/competencies

See https://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------

After installing the plugin, it is ready to use without the need for any configuration.

Teachers can add a Competency restriction when editing an activity or course section, provided the course uses a competency framework and has competencies linked to the course. They select one competency; access is granted when the learner is proficient in that competency (global status, not limited to the current course).

If you want to learn more about using availability plugins in Moodle, please see https://docs.moodle.org/en/Restrict_access.


Capabilities
------------

This plugin also introduces these additional capabilities:

### availability/competencies:addinstance

This capability controls who is able to add competencies conditions to activities.
It is assigned to the manager and to the editing teacher role by default.

Withdrawing this capability only hides the restriction from the "Add restriction..." dialogue.
Competencies restrictions which have already been added continue to apply and can still be removed.


Scheduled Tasks
---------------

This plugin does not add any additional scheduled tasks.


How this plugin works
---------------------

This availability condition stores a single competency ID in the availability JSON tree. The evaluation of the condition uses the learner's global competency proficiency. If competencies are disabled site-wide, or the referenced competency is invalid, access is denied.


Theme support
-------------

This plugin is developed and tested on Moodle Core's Boost theme.
It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.


Plugin repositories
-------------------

This plugin is published and regularly updated in the Moodle plugins repository:
http://moodle.org/plugins/view/availability_competencies

The latest development version can be found on Github:
https://github.com/ssystems-de/moodle-availability_competencies


Bug and problem reports
-----------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.

Please report bugs and problems on Github:
https://github.com/ssystems-de/moodle-availability_competencies/issues


Community feature proposals
---------------------------

The functionality of this plugin is primarily implemented for the needs of our clients and published as-is to the community. We are aware that members of the community will have other needs and would love to see them solved by this plugin.

Please issue feature proposals on Github:
https://github.com/ssystems-de/moodle-availability_competencies/issues

Please create pull requests on Github:
https://github.com/ssystems-de/moodle-availability_competencies/pulls


Paid support
------------

We are always interested to read about your issues and feature proposals or even get a pull request from you on Github. However, please note that our time for working on community Github issues is limited.

As solution provider, we also offer paid support for this plugin. If you are interested, please have a look at our services on [ssystems.de](https://www.ssystems.de/) or get in touch with us directly via vertrieb@ssystems.de.


Moodle release support
----------------------

This plugin is only maintained for the most recent major release of Moodle as well as the most recent LTS release of Moodle. Bugfixes are backported to the LTS release. However, new features and improvements are not necessarily backported to the LTS release.

Apart from these maintained releases, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that this plugin still works with a new major release - please let us know on Github.

If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is an undeniable risk for erratic behavior.


Translating this plugin
-----------------------

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

As the plugin creator, we manage the translation into german for our own local needs on AMOS. Please contribute your translation into all other languages in AMOS where they will be reviewed by the official language pack maintainers for Moodle.


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send us a pull request on Github with modifications.


Maintainers
-----------

The plugin is maintained by\
ssystems GmbH


Copyright
---------

The copyright of this plugin is held by\
ssystems GmbH

Individual copyrights of individual developers are tracked in PHPDoc comments and Git commits.
