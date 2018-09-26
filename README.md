# northstack-client
PHP Library to access the NorthStack NorthStack APIs

# Installation

Have a working docker install

The client is dockerized run, so you can checkout the client and run the install script
to build a local docker image and add the wrapper to `/usr/local/bin/northstack`

```
git clone git@github.com:pagely/northstack.git
cd northstack
sudo ./bin/install.sh
```

## OS Support

Our goal is to support this client anywhere that docker can run.  Currently the
wrapper scripts only work Linux and Mac.  Windows wrappers scripts are still a todo.

# Using the client

## Login
```
./bin/northstack auth:login my@northstack-username.com
```

This will save a login token to `~/.northstacklogin`. The login will be good for 14 hours.  After that you will need to login again.
This file will automatically be read by all other commands and used for authorization
to the NorthStack API.

You may also use your API keys to log in using the `auth:client-login` command.

When done, you may use the `auth:logout` command or simply remove the `~/.northstacklogin` file.

## Commands

Executing the `northstack` command by itself will show the commands available.
```
./bin/northstack
```

To get usage help for any command, simply prefix the command name with `help`
```
./bin/northstack help auth:login
```

## Help!

### What is my Account ID?
1. You can get your account ID by logging into https://atomic-beta.pagely.com and
looking at the address in your browser. Your account ID will be the number directly following `/account/` in the address.

2. If you are a collaborator and need the ID for another account, log in to atomic-beta (link above) and use the account switcher
(click your name in the upper right) and switch to the account in question. The address in your browser
will change to reflect the account ID you are now looking at.
