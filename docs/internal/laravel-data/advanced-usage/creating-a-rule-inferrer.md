# Creating a rule inferrer

Rule inferrers will try to infer validation rules for properties within a data object.

A rule inferrer can be created by implementing the RuleInferrer interface:

```
interfaceRuleInferrer{publicfunctionhandle(DataProperty$property,PropertyRules$rules,ValidationContext$context):PropertyRules;
}
```

A collection of previous inferred rules is given, and a DataProperty object which represents the property for which the value is transformed. You can read more about the internal structures of the package here.

The ValidationContext is also injected, this contains the following info:

- payload the current payload respective to the data object which is being validated
- fullPayload the full payload which is being validated
- validationPath the path from the full payload to the current payload

The RulesCollection contains all the rules for the property represented as ValidationRule objects.

You can add new rules to it:

```
$rules->add(newMin(42));
```

When adding a rule of the same kind, a previous version of the rule will be removed:

```
$rules->add(newMin(42));$rules->add(newMin(314));$rules->all();// [new Min(314)]
```

Adding a string rule can be done as such:

```
$rules->add(newRule('min:42'));
```

You can check if the collection contains a type of rule:

```
$rules->hasType(Min::class);
```

Or remove certain types of rules:

```
$rules->removeType(Min::class);
```

In the end, a rule inferrer should always return a RulesCollection.

Rule inferrers need to be manually defined within the data.php config file.

Creating a transformer

Use with Inertia

Help us improve this page

Flare

An error tracker especially made for Laravel

Learn more

Help us improve this page

- Products
- Open Source
- Courses
- Web Development

VacanciesAboutBlogDocsGuidelinesMerch ↗

Log in

Kruikstraat 22, Box 12

2018 Antwerp, Belgium

info@spatie.be

+32 3 292 56 79

- GitHub
- Instagram
- LinkedIn
- Twitter
- Bluesky
- Mastodon
- YouTube

- Privacy
- Disclaimer

+32 3 292 56 79

Our office is closed now, email us instead

ESC