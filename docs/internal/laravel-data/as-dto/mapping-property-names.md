# Mapping property names

### On this page

1. Mapping Nested Properties

Sometimes the property names in the array from which you're creating a data object might be different. You can define another name for a property when it is created from an array using attributes:

```
classContractDataextendsData{publicfunction__construct(publicstring$name,#[MapInputName('record_company')]publicstring$recordCompany,) {
    }
}
```

Creating the data object can now be done as such:

```
ContractData::from(['name'=>'Rick Astley','record_company'=>'RCA Records']);
```

Changing all property names in a data object to snake\_case in the data the object is created from can be done as such:

```
#[MapInputName(SnakeCaseMapper::class)]classContractDataextendsData{publicfunction__construct(publicstring$name,publicstring$recordCompany,) {
    }
}
```

You can also use the MapName attribute when you want to combine input (see transforming data objects) and output property name mapping:

```
#[MapName(SnakeCaseMapper::class)]classContractDataextendsData{publicfunction__construct(publicstring$name,publicstring$recordCompany,) {
    }
}
```

It is possible to set a default name mapping strategy for all data objects in the data.php config file:

```
'name_mapping_strategy'=> ['input'=>SnakeCaseMapper::class,'output'=>null,
],
```

## # # Mapping Nested Properties

You can also map nested properties using dot notation in the MapInputName attribute. This is useful when you want to extract a nested value from an array and assign it to a property in your data object:

```
classSongDataextendsData{publicfunction__construct(#[MapInputName("title.name")]publicstring$title,#[MapInputName("artists.0.name")]publicstring$artist) {
    }
}
```

You can create the data object from an array with nested structures:

```
SongData::from(["title"=> ["name"=>"Never gonna give you up"],"artists"=> [
        ["name"=>"Rick Astley"]
    ]
]);
```

The package has a set of default mappers available, you can find them here.

Optional properties

Default values

Help us improve this page

### On this page

- Mapping Nested Properties

Writing Readable PHP

Learn everything about maintainable code in our online course

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