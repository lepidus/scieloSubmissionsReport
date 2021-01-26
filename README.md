# Submissions Report Plugin 

This plugin generates a **CSV** spreadsheet with the following information:
- Submission Id
- Submission title
- Submission user
- Submission date
- Decision date
- Days until decision made
- Submission status (review, approved, rejected)
- Preprint publication status (not sent for publication in a journal, sent, sent and accepted)
- Preprint publication DOI (if it has been published in a journal)
- Submission area moderator
- Submission moderators
- Server name
- Submission Section
- Submission Locale
- Authors (containing their names, countries and affiliation)
- Submission notes

__Copyright (c) Lepidus Tecnologia__ 

# First steps

## Prerequisites

* OPS 3.2.1


## Plugin Download

To download the plugin, go to the Releases page [clicking here](https://gitlab.lepidus.com.br/plugins_ojs/relatorioscielo/-/releases), or go to `RelatorioScielo> Project Overview> Releases` and check the version you want to install.

## Installation

1. Enter the administration area of ​​your OPS website through the __Dashboard__.
2. Navigate to `Settings`>` Website`> `Plugins`> `Upload a new plugin`.
3. Under __Upload file__ select the file __SubmissionReportPlugin.tar.gz__.
4. Click __Save__ and the plugin will be installed on your OPS.

# Technologies Used

* CSS
* PHP 7.2.24
* Smarty 3.1.32
* MariaDB 10.1.43

# License
__This plugin is licensed under the GNU General Public License v2__
