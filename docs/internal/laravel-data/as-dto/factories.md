# Factories

### On this page

1. Disable property name mapping
2. Changing the validation strategy
3. Disabling magic methods
4. Disabling optional values
5. Adding additional global casts
6. Using the creation context

It is possible to automatically create data objects in all sorts of forms with this package. Sometimes a little bit more

control is required when a data object is being created. This is where factories come in.

Factories allow you to create data objects like before but allow you to customize the creation process.

For example, we can create a data object using a factory like this:

```
SongData::factory()->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

Collecting a bunch of data objects using a factory can be done as such:

```
SongData::factory()->collect(Song::all())
```

## # # Disable property name mapping

We saw earlier that it is possible to map

property names when creating a data object from an array. This can be disabled when using a factory:

```
ContractData::factory()->withoutPropertyNameMapping()->from(['name'=>'Rick Astley','record_company'=>'RCA Records']);// record_company will not be mapped to recordCompany
```

## # # Changing the validation strategy

By default, the package will only validate Requests when creating a data object it is possible to change the validation

strategy to always validate for each type:

```
SongData::factory()->alwaysValidate()->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

Or completely disable validation:

```
SongData::factory()->withoutValidation()->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

## # # Disabling magic methods

A data object can be created

using magic methods , this can be disabled

when using a factory:

```
SongData::factory()->withoutMagicalCreation()->from('Never gonna give you up');// Won't work since the magical method creation is disabled
```

It is also possible to ignore the magical creation methods when creating a data object as such:

```
SongData::factory()->ignoreMagicalMethod('fromString')->from('Never gonna give you up');// Won't work since the magical method is ignored
```

## # # Disabling optional values

When creating a data object that has optional properties, it is possible choose whether missing properties from the payload should be created as Optional. This can be helpful when you want to have a null value instead of an Optional object - for example, when creating the DTO from an Eloquent model with null values.

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,publicOptional|null|string$album,) {
    }
}SongData::factory()
    ->withoutOptionalValues()
    ->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);// album will `null` instead of `Optional`
```

Note that when an Optional property has no default value, and is not nullable, and the payload does not contain a value for this property, the DTO will not have the property set - so accessing it can throw Typed property must not be accessed before initialization error. Therefore, it's advisable to either set a default value or make the property nullable, when using withoutOptionalValues.

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,publicOptional|string$album,// careful here!publicOptional|string$publisher= 'unknown',publicOptional|string|null$label,) {
    }
}$data=SongData::factory()
    ->withoutOptionalValues()
    ->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);$data->toArray();// ['title' => 'Never gonna give you up', 'artist' => 'Rick Astley', 'publisher' => 'unknown', 'label' => null]$data->album;// accessing the album will throw an error, unless the property is set before accessing it
```

## # # Adding additional global casts

When creating a data object, it is possible to add additional casts to the data object:

```
SongData::factory()->withCast('string',StringToUpperCast::class)->from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

These casts will not replace the other global casts defined in the data.php config file, they will though run before

the other global casts. You define them just like you would define them in the config file, the first parameter is the

type of the property that should be cast and the second parameter is the cast class.

## # # Using the creation context

Internally the package uses a creation context to create data objects. The factory allows you to use this context manually, but when using the from method it will be used automatically.

It is possible to inject the creation context into a magical method by adding it as a parameter:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionfromModel(Song$song,CreationContext$context):self{// Do something with the context}
}
```

You can read more about creation contexts here.

Injecting property values

Introduction

Help us improve this page

### On this page

- Disable property name mapping
- Changing the validation strategy
- Disabling magic methods
- Disabling optional values
- Adding additional global casts
- Using the creation context

Mailcoach

Check out our full-featured (self-hosted) email marketing solution

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