# northstack-client
CLI Client and PHP Library to access the NorthStack NorthStack APIs

# Installation

## Requirements

There are two ways to install and run the NorthStack CLI. The primary means is a native install (which requires you to have PHP 7.2 installed) and a docker-based wrapper which does not. Both installation methods require you to have docker installed.

| requirement | native | docker |
|--|---|---|
| docker | 17.09+ | 17.09+ |
| php | 7.2+ | N/A |
| OS | Linux or OSX | Linux |

The native install method is preferred and more performant, so it's best if you're able to install PHP >= 7.2 for your operating system. If you're running a modern Linux distro chances are you can just `$packageManager install php72`. If you're using on OSX the easiest way to do this is use [homebrew](https://brew.sh/).

## Install

```
git clone git@github.com:northstack/northstack-client.git
cd northstack-client
./bin/install.sh
```

## Post-install

By default the installation script installs the CLI to `~/.local/bin`, so you'll want to add this directory to your `$PATH`:

```bash
$ echo "PATH=\$HOME/.local/bin:\$PATH" >> ~/.bashrc
```

The CLI also supports tab-completion. The process for enabling this varies from platform to platform, but in a pinch you can also just add the completion hook to your bashrc:

```bash
$ ~/.local/bin/northstack _completion --generate-hook --program northstack >> ~/.bashrc
```

# Using the client

## Login
```
northstack auth:login my@northstack-username.com
```

This will save a login token to `~/.northstacklogin`. The login will be good for 14 hours. 
After that you will need to login again.

This file will automatically be read by all other commands and used for authorization
to the NorthStack API.

You may also use your API keys to log in using the `auth:client-login` command.

When done, you may use the `auth:logout` command or simply remove the `~/.northstacklogin` file.

## Commands

Executing the `northstack` command by itself will show the commands available.
```
northstack
```

To get usage help for any command, simply prefix the command name with `help`
```
northstack help auth:login
```

## Help!

### What is my Account ID?
1. You can get your account ID by logging into https://console.northstack.com and
looking at the address in your browser. Your account ID will be displayed on the 
dashboard labeled `Account ID`.

2. If you are a collaborator and need the ID for another account, log in to console (link above) 
and use the account switcher (click your name in the upper right) and switch to the organization 
in question. The `Account ID` on the dashboardwill change to reflect the current organization that 
you are viewing.
