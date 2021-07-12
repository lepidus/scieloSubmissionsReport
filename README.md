# SciELO Submissions Report Plugin

This plugin generates a **CSV** spreadsheet with information that is usually requested by SciELO, based on the website submissions. It allows the user to filter submissions within a date interval, using for this the submission date, last decision date or both. The user can also filter from which sections the submissions should be obtained.

Since this plugin can be used in OJS and OPS, the informations change for each application.

The common information obtained for OJS and OPS are:
- Submission ID
- Submission title
- Submitter
- Date submitted
- Days until status changing
- Submission status (queued, published, declined or scheduled)
- Submission section
- Submission language
- Authors (containing full name, country and affiliation to each one)
- Final decision
- Final decision date
- Time in reviewing
- Time between submission and final decision
- Average time in reviewing
- Sections chosen for filtering

The information obtained only in OJS are:
- Journal editors assigned
- Section editor assigned
- Reviewing recommendations
- Last decision

The information obtained only in OPS are:
- Preprint publication status (not submitted for publication in journal, submitted or submitted and published)
- Preprint publication DOI (if it has been published in a journal)
- Section moderator assigned
- Moderators assigned
- Submission notes

## Compatibility

The latest release of this plugin is compatible with the following PKP applications:

* OJS 3.2.1
* OPS 3.2.1


## Plugin Download

To download the plugin, go to the [Releases page](https://github.com/lepidus/scieloSubmissionsReport/releases) and download the tar.gz package of the latest release compatible with your website.

## Installation

1. Enter the administration area of ​​your OJS/OPS website through the __Dashboard__.
2. Navigate to `Settings`>` Website`> `Plugins`> `Upload a new plugin`.
3. Under __Upload file__ select the file __ScieloSubmissionsReportPlugin.tar.gz__.
4. Click __Save__ and the plugin will be installed on your website.

## Unit Test for Development

To run the tests your plugin can't be installed in OJS/OPS through a symbolic link,
if it is, you must copy/move it directly to the plugins/report/ directory.

Then, you can run this command at application's root directory:

``` bash
find plugins/reports/scieloSubmissionsReport -name tests -type d -exec php lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration lib/pkp/tests/phpunit-env2.xml --exclude-group oppositeApplication -v "{}" ";"
```

Replace `oppositeApplication` with the application that tests will not run. E.g.: OJS or OPS

Example command to run OJS tests:

``` bash
find plugins/reports/scieloSubmissionsReport -name tests -type d -exec php lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration lib/pkp/tests/phpunit-env2.xml --exclude-group OPS -v "{}" ";"
```

# License
__This plugin is licensed under the GNU General Public License v3.0__

__Copyright (c) 2019-2021 Lepidus Tecnologia__

__Copyright (c) 2020-2021 SciELO__
