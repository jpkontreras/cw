# State scopes

Every model using the HasStates trait will have these scopes available:

- whereState($column, $states) and orWhereState($column, $states)
- whereNotState($column, $states) and orWhereNotState($column, $states)

```
$payments=Payment::whereState('state',Paid::class);$payments=Payment::whereState('state', [Pending::class,Paid::class]);$payments=Payment::whereState('state',Pending::class)->orWhereState('state',Paid::class);$payments=Payment::whereNotState('state',Pending::class);$payments=Payment::whereNotState('state', [Failed::class,Canceled::class]);$payments=Payment::whereNotState('state',Failed::class)->orWhereNotState('state',Canceled::class);
```

When the state field has another column name in the query (for example due to a join), it is possible to use the full column name:

```
$payments=Payment::whereState('payments.state',Paid::class);$payments=Payment::whereNotState('payments.state',Pending::class);
```

Custom default transition class

State validation rule

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