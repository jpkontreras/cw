# Pipeline

### On this page

1. Preparing data for the pipeline
2. Extending the pipeline within your data class

The data pipeline allows you to configure how data objects are constructed from a payload. In the previous chapter we

saw that a data object created from a payload will be first normalized into an array. This array is passed into the

pipeline.

The pipeline exists of multiple pipes which will transform the normalized data into a collection of property values

which can be passed to the data object constructor.

By default, the pipeline exists of the following pipes:

- AuthorizedDataPipe checks if the user is authorized to perform the request
- MapPropertiesDataPipe maps the names of properties
- FillRouteParameterPropertiesDataPipe fills property values from route parameters
- ValidatePropertiesDataPipe validates the properties
- DefaultValuesDataPipe adds default values for properties when they are not set
- CastPropertiesDataPipe casts the values of properties

Each result of the previous pipe is passed on into the next pipe, you can define the pipes on an individual data object

as such:

```
classSongDataextendsData{publicfunction__construct(// ...) {
    }publicstaticfunctionpipeline():DataPipeline{returnDataPipeline::create()
            ->into(static::class)
            ->through(AuthorizedDataPipe::class)
            ->through(MapPropertiesDataPipe::class)
            ->through(FillRouteParameterPropertiesDataPipe::class)
            ->through(ValidatePropertiesDataPipe::class)
            ->through(DefaultValuesDataPipe::class)
            ->through(CastPropertiesDataPipe::class);
    }
}
```

Each pipe implements the DataPipe interface and should return an array of properties:

```
interfaceDataPipe{publicfunctionhandle(mixed$payload,DataClass$class,array$properties,CreationContext$creationContext):array;
}
```

The handle method has several arguments:

- payload the non normalized payload
- class the DataClass object for the data object more info
- properties the key-value properties which will be used to construct the data object
- creationContext the context in which the data object is being created you'll find the following info here:
    - dataClass the data class which is being created
    - validationStrategy the validation strategy which is being used
    - mapPropertyNames whether property names should be mapped
    - disableMagicalCreation whether to use the magical creation methods or not
    - ignoredMagicalMethods the magical methods which are ignored
    - casts a collection of global casts

When using a magic creation methods, the pipeline is not being used (since you manually overwrite how a data object is

constructed). Only when you pass in a request object a minimal version of the pipeline is used to authorize and validate

the request.

## # # Preparing data for the pipeline

Sometimes you need to make some changes to the payload after it has been normalized, but before they are sent into the data pipeline. You can do this using the prepareForPipeline method as follows:

```
classSongMetadata{publicfunction__construct(publicstring$releaseYear,publicstring$producer,) {}
}classSongDataextendsData{publicfunction__construct(publicstring$title,publicSongMetadata$metadata,) {}publicstaticfunctionprepareForPipeline(array$properties):array{$properties['metadata'] =Arr::only($properties, ['release_year','producer']);return$properties;
    }
}
```

Now it is possible to create a data object as follows:

```
$songData=SongData::from(['title'=>'Never gonna give you up','release_year'=>'1987','producer'=>'Stock Aitken Waterman',
]);
```

## # # Extending the pipeline within your data class

Sometimes you want to send your payload first through a certain pipe without creating a whole new pipeline, this can be done as such:

```
classSongDataextendsData{publicstaticfunctionpipeline():DataPipeline{returnparent::pipeline()->firstThrough(GuessCasingForKeyDataPipe::class);
    }
}
```

Normalizers

Creating a cast

Help us improve this page

### On this page

- Preparing data for the pipeline
- Extending the pipeline within your data class

Ray

Debug your applications faster

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