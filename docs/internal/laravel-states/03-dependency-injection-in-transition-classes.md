# Dependency injection in transition classes

Just like Laravel jobs, you're able to inject dependencies automatically in the handle() method of every transition.

```
classTransitionWithDependencyextendsTransition{// …publicfunctionhandle(Dependency$dependency)
    {// $dependency is resolved from the container}
}
```

Note: be careful not to have too many side effects within a transition. If you're injecting many dependencies, it's probably a sign that you should refactor your code and use an event-based system to handle complex side effects.

Custom transition classes

Retrieving transitionable states

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