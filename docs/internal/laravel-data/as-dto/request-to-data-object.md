# From a request

### On this page

1. Getting the data object filled with request data from anywhere
2. Validating a collection of data objects:

You can create a data object by the values given in the request.

For example, let's say you send a POST request to an endpoint with the following data:

```
{"title":"Never gonna give you up","artist":"Rick Astley"}
```

This package can automatically resolve a SongData object from these values by using the SongData class we saw in an

earlier chapter:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }
}
```

You can now inject the SongData class in your controller. It will already be filled with the values found in the

request.

```
classUpdateSongController{publicfunction__invoke(Song$model,SongData$data){$model->update($data->all());returnredirect()->back();
    }
}
```

As an added benefit, these values will be validated before the data object is created. If the validation fails, a ValidationException will be thrown which will look like you've written the validation rules yourself.

The package will also automatically validate all requests when passed to the from method:

```
classUpdateSongController{publicfunction__invoke(Song$model,SongRequest$request){$model->update(SongData::from($request)->all());returnredirect()->back();
    }
}
```

We have a complete section within these docs dedicated to validation, you can find it here.

## # # Getting the data object filled with request data from anywhere

You can resolve a data object from the container.

```
app(SongData::class);
```

We resolve a data object from the container, its properties will already be filled by the values of the request with matching key names.

If the request contains data that is not compatible with the data object, a validation exception will be thrown.

## # # Validating a collection of data objects:

Let's say we want to create a data object like this from a request:

```
classAlbumDataextendsData{publicfunction__construct(publicstring$title,#[DataCollectionOf(SongData::class)]publicDataCollection$songs,) {
    }
}
```

Since the SongData has its own validation rules, the package will automatically apply them when resolving validation

rules for this object.

In this case the validation rules for AlbumData would look like this:

```
['title'=> ['required','string'],'songs'=> ['required','array'],'songs.*.title'=> ['required','string'],'songs.*.artist'=> ['required','string'],
]
```

Computed values

From a model

Help us improve this page

### On this page

- Getting the data object filled with request data from anywhere
- Validating a collection of data objects:

Laravel beyond CRUD

Check out our course on Laravel development for large apps

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