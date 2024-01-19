# WordPress Log Manager

A simple plugin to list and manage WordPress logs, and to clear them regularly using cron tasks.

In this plugin you can set a size limit for the log file, if the limit is reached on the next cron run, it will be moved to another folder, and zipped to preserve space. The size limit and cron timing can be set in the plugin setting page. Archived logs can also be listed and managed in the settings page.

The plugin will warn the user using the admin email, or on a secondary email, if the limit is reached. The plugin can also warn using a Slack webhook (hardcoded in, since this was meant for e-labo internal uses only, this setting could easily be made into another field in the settings).

Plugin made for [e-labo - 2023](https://e-labo.biz/). Depends on EDD files but removed for privacy reasons.