TablePress-FooTable
===================

An extension to the [TablePress](http://tablepress.org/) plugin adding support for [FooTable](https://github.com/bradvin/FooTable).

##How It Works

In order to enable the FooTable features, some extra content must be added to the first line of your table. Let's call this content `triggers`.

All triggers will be of the for `#trigger#`. For instance, to enable the toggle feature of FooTable, we will add the trigger `toggle`:

    My cell content #toggle#

Here is the list of supported triggers (we will add more later):

* `#toggle#`
* `#hide-phone#`
* `#hide-tablet#`

### Example
![](http://i.imgur.com/9kKcGab.png)