# Creating a transformer

### On this page

1. Combining transformers and casts

Transformers take complex values and transform them into simple types. For example, a Carbon object could be transformed to 16-05-1994T00:00:00+00.

A transformer implements the following interface:

```
interfaceTransformer{publicfunctiontransform(DataProperty$property,mixed$value,TransformationContext$context):mixed;
}
```

The following parameters are provided:

- property: a DataProperty object which represents the property for which the value is transformed. You can read more about the internal structures of the package here
- value: the value that should be transformed, this will never be null
- context: a TransformationContext object which contains the current transformation context with the following properties:
    - transformValues indicates if values should be transformed or not
    - mapPropertyNames indicates if property names should be mapped or not
    - wrapExecutionType the execution type that should be used for wrapping values
    - transformers a collection of transformers that can be used to transform values

In the end, the transformer should return a transformed value.

## # # Combining transformers and casts

You can transformers and casts in one class:

```
classToUpperCastAndTransformerimplementsCast, Transformer{publicfunctioncast(DataProperty$property,mixed$value,array$properties,CreationContext$context):string{returnstrtoupper($value);
    }publicfunctiontransform(DataProperty$property,mixed$value,TransformationContext$context):string{returnstrtoupper($value);
    }
}
```

Within your data object, you can use the WithCastAndTransformer attribute to use the cast and transformer:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[WithCastAndTransformer(SomeCastAndTransformer::class)]publicstring$artist,) {
    }
}
```

Creating a cast

Creating a rule inferrer

Help us improve this page

### On this page

- Combining transformers and casts

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