
This guide explains how to configure the Job Summarization software.

## Prerequisites

The Job Performance (SUPReMM) XDMoD module must be [installed](supremm-install.md) and [configured](supremm-configuration.md)
before configuring the Job Summarization software. 

Setup Script
------------

The Job Summarization software includes a setup script to help you configure your
installation. The script will prompt for information needed to configure the
software and update the configuration files and databases. If you have
modified your configuration files manually, be sure to make backups before
running this command:

    # supremm-setup

The setup script needs to be run as a user that has write access to the
configuration files. You may either specify a writable path name when prompted
(and then manually copy the generated configuration files) or run the script as
the root user.

The setup script has an interactive ncurses-based menu-driven interface. A description of
the main menu options is below:

### Create configuration file

This section prompts for the configuration settings for the XDMoD datawarehouse
and the MongoDB database. The script will automatically detect the resources
from the XDMoD datawarehouse and prompt for the settings for each of them.

### Create database tables

This section will create the database tables that are needed for the job summarization software.

The default connection settings are read from the configuration file (but can
be overridden). It is necessary to supply username and password of a
database user account that has CREATE privileges on the XDMoD modw_supremm database.

### Initialize MongoDB Database

This section will add required data to the MongoDB database.

The default connection settings are read from the configuration file (but can
be overridden).

Configuration Guide
-------------------

The SUPReMM job summarization software is configured using a json-style format
file that uses json syntax but permits line-based comments (lines starting with
`//` are ignored by the parser).

This file is stored in `/etc/supremm/config.json` for RPM based installs or
under `[PREFIX]/etc/supremm/config.json` for source code installs, where
`[PREFIX]` is the path that was passed to the install script.

The paths shown in this configuration guide show the default values for
RPM-based installs.  For source code installs you will need to adjust the paths
in the examples to match the installed location of the package.

Resource settings
-----------------
The "my_cluster_name" string and value of the `resource_id` field should be set to
the same values as the `code` and `id` columns in the Open XDMoD
modw.resourcefact table in the datawarehouse.

The `pcp_log_dir` field should be set to the path to the PCP node-level
archives. If the job scheduler is configured to store a copy of each job batch
script, then the `script_dir` field should be set to the path to the directory
that contains the job batch scripts. If the job batch scripts are not
available, then the `script_dir` field should be set to an empty string.

```json
{
    ...
    "resources": {
        "my_cluster_name": {
            "enabled": true,
            "resource_id": 1,
            "batch_system": "XDMoD",
            "hostname_mode": "hostname",
            "pcp_log_dir": "/data/pcp-logs/my_cluster_name",
            "script_dir": "/data/jobscripts/my_cluster_name"
        }
    }
}
```

Database authentication settings
--------------------------------

The configuration file supports two different mechanisms to specify the access
credentials for the Open XDMoD datawarehouse. **Choose one of these options.** Either:
1. Specify the path to the Open XDMoD install location (and the code will use the Open XDMoD configuration directly) or
2. Specify the location and access credentials directly.

If the summarization software is installed on the same machine as Open XDMoD then (1) is the recommended option. Otherwise use option (2).

### Option (1) XDMoD path specification ###

If the summarization software is installed on the same machine as Open XDMoD
then ensure the `config.json` has the following settings:

```json
{
    ...
    "xdmodroot": "/etc/xdmod",
    "datawarehouse": {
        "include": "xdmod://datawarehouse"
    },
}
```

Where xdmodroot should be set to the location of the xdmod configuration
directory, typically `/etc/xdmod` for RPM based installs. Note that the user
account that runs the summarization scripts will need to have read permission
on the xdmod configuration files. For an RPM based install, the `xdmod` user
account has the correct permission.

### Option (2) Direct DB credentials ###

If the summarization software is installed on a dedicated machine (separate
from the Open XDMoD server), then the XDMoD datawarehouse location and access credentials
should be specified as follows:

Create a file called `.supremm.my.cnf` in the home directory of the user that
will run the job summarization software. This file must include the username
and password to the Open XDMoD datawarehouse mysql server:

```ini
[client]
user=[USERNAME]
password=[PASSWORD]
```

ensure the "datawarehouse" section of the `config.json` file has settings like
the following, where *XDMOD\_DATABASE\_FILL\_ME\_IN* should be set to the hostname of
the XDMoD database server.

```json
{
    ...
    "datawarehouse": {
        "db_engine": "MySQLDB",
        "host": "XDMOD_DATABASE_FILL_ME_IN",
        "defaultsfile": "~/.supremm.my.cnf"
    },
}
```

MongoDB settings
----------------

If you used _Option (1) XDMoD path specification_ in the datawarehouse configuration then
use the following configuration settings:

```json
{
    ...
    "outputdatabase": {
        "include": "xdmod://jobsummarydb"
    }
}
```

Otherwise the MongoDB settings can be specified directly as follows:
The `outputdatabase`.`uri` should be set to the uri of the MongoDB server that
will be used to store the job level summary documents.  The uri syntax is
described in the [MongoDB documentation][]. You must specify the database name in
the connection uri string in addition to specifying it in the `dbname` field

```json
{
    ...
    "outputdatabase": {
        "type": "mongodb",
        "uri": "mongodb://localhost:27017/supremm",
        "dbname": "supremm"
    },
}
```

[MongoDB documentation]:        https://docs.mongodb.org/manual/reference/connection-string/


Setup the Database
-------------------

The summarization software uses relational database tables to keep track of
which jobs have been summarized, when and which version of the software was
used. These tables are added to the `modw_supremm` schema that was created when
the Open XDMoD SUPReMM module was installed.  The database creation script is
located in the `/usr/share/supremm/setup` directory and should be run on the
XDMoD datawarehouse DB instance.

    $ mysql -u root -p < /usr/share/supremm/setup/modw_supremm.sql


Setup MongoDB
-----------

    $ mongo [MONGO CONNECTION URI] /usr/share/supremm/setup/mongo_setup.js

where [MONGO CONNECTION URI] is the uri of the MongoDB database.
