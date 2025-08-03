# Skipping validation

### On this page

1. Skipping validation for all properties

Sometimes you don't want properties to be automatically validated, for instance when you're manually overwriting the

rules method like this:

```
classSongDataextendsData{publicfunction__construct(publicstring$name,) {
    }publicstaticfunctionfromRequest(Request$request):static{returnnewself("{$request->input('first_name')} {$request->input('last_name')}")
    }publicstaticfunctionrules():array{return['first_name'=> ['required','string'],'last_name'=> ['required','string'],
        ];
    }
}
```

When a request is being validated, the rules will look like this:

```
['name'=> ['required','string'],'first_name'=> ['required','string'],'last_name'=> ['required','string'],
]
```

We know we never want to validate the name property since it won't be in the request payload, this can be done as

such:

```
classSongDataextendsData{publicfunction__construct(#[WithoutValidation]publicstring$name,) {
    }
}
```

Now the validation rules will look like this:

```
['first_name'=> ['required','string'],'last_name'=> ['required','string'],
]
```

## # # Skipping validation for all properties

By using data factories or setting the validation\_strategy in the data.php config you can skip validation for all properties of a data class.

Nesting Data

From data to array

Help us improve this page

### On this page

- Skipping validation for all properties

Testing Laravel

Learn how to write quality tests in Pest and PHPUnit in our video course

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