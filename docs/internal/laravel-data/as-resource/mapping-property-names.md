# Mapping property names

Sometimes you might want to change the name of a property in the transformed payload, with attributes this is possible:

```
classContractDataextendsData{publicfunction__construct(publicstring$name,#[MapOutputName('record_company')]publicstring$recordCompany,) {
    }
}
```

Now our array looks like this:

```
['name'=>'Rick Astley','record_company'=>'RCA Records',
]
```

Changing all property names in a data object to snake\_case as output data can be done as such:

```
#[MapOutputName(SnakeCaseMapper::class)]classContractDataextendsData{publicfunction__construct(publicstring$name,publicstring$recordCompany,) {
    }
}
```

You can also use the MapName attribute when you want to combine input and output property name mapping:

```
#[MapName(SnakeCaseMapper::class)]classContractDataextendsData{publicfunction__construct(publicstring$name,publicstring$recordCompany,) {
    }
}
```

It is possible to set a default name mapping strategy for all data objects in the data.php config file:

```
'name_mapping_strategy'=> ['input'=>null,'output'=>SnakeCaseMapper::class,
],
```

You can now create a data object as such:

```
$contract=newContractData(name:'Rick Astley',recordCompany:'RCA Records',
);
```

And a transformed version of the data object will look like this:

```
['name'=>'Rick Astley','record_company'=>'RCA Records',
]
```

The package has a set of default mappers available, you can find them here.

From data to resource

Appending properties

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