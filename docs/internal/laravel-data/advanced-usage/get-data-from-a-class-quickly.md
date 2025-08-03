# Get data from a class quickly

By adding the WithData trait to a Model, Request or any class that can be magically be converted to a data object,

you'll enable support for the getData method. This method will automatically generate a data object for the object it

is called upon.

For example, let's retake a look at the Song model we saw earlier. We can add the WithData trait as follows:

```
classSongextendsModel{useWithData;protected$dataClass=SongData::class;
}
```

Now we can quickly get the data object for the model as such:

```
Song::firstOrFail($id)->getData();// A SongData object
```

We can do the same with a FormRequest, we don't use a property here to define the data class but use a method instead:

```
classSongRequestextendsFormRequest{useWithData;protectedfunctiondataClass():string{returnSongData::class;
    }
}
```

Now within a controller where the request is injected, we can get the data object like this:

```
classSongController{publicfunction__invoke(SongRequest$request):SongData{$data=$request->getData();$song=Song::create($data->toArray());return$data;
    }
}
```

Validation attributes

Performance

Help us improve this page

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