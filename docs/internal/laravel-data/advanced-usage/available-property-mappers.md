# Available property mappers

In previous sections we've already seen how

to create data objects where the keys of the

payload differ from the property names of the data object. It is also possible

to transform data objects to an

array/json/... where the keys of the payload differ from the property names of the data object.

These mappings can be set manually put the package also provide a set of mappers that can be used to automatically map

property names:

```
classContractDataextendsData{publicfunction__construct(#[MapName(CamelCaseMapper::class)]publicstring$name,#[MapName(SnakeCaseMapper::class)]publicstring$recordCompany,#[MapName(newProvidedNameMapper('country field'))]publicstring$country,#[MapName(StudlyCaseMapper::class)]publicstring$cityName,#[MapName(LowerCaseMapper::class)]publicstring$addressLine1,#[MapName(UpperCaseMapper::class)]publicstring$addressLine2,) {
    }
}
```

Creating the data object can now be done as such:

```
ContractData::from(['name'=>'Rick Astley','record_company'=>'RCA Records','country field'=>'Belgium','CityName'=>'Antwerp','addressline1'=>'some address line 1','ADDRESSLINE2'=>'some address line 2',
]);
```

When transforming such a data object the payload will look like this:

```
{"name":"Rick Astley","record_company":"RCA Records","country field":"Belgium","CityName":"Antwerp","addressline1":"some address line 1","ADDRESSLINE2":"some address line 2"}
```

In Packages

Help us improve this page

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