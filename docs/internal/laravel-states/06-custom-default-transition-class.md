# Custom default transition class

When working with state transitions, you may need to pass additional contextual data to your StateChanged event

listeners. While custom transitions allow this for specific state changes, sometimes you need this functionality for all

transitions. To handle such scenarios DefaultTransition class can be extended.

The following example uses different logic depending on how transitionTo is called.

Creating custom default transition class:

```
useSpatie\ModelStates\DefaultTransition;useSpatie\ModelStates\State;classCustomDefaultTransitionWithAttributesextendsDefaultTransition{publicfunction__construct($model,string$field,State$newState,publicbool$silent=false)
    {parent::__construct($model,$field,$newState);
    }
}
```

Implement your state change listener to use the custom parameter:

```
useSpatie\ModelStates\Events\StateChanged;classOrderStateChangedListener{publicfunctionhandle(StateChanged$event):void{$isSilent=$event->transition->silent;$this->processOrderState($event->model);if(!$isSilent) {$this->notifyUser($event->model);
        }
    }
}
```

Now we can pass additional parameter to transitionTo method, to omit notification logic:

```
classOrderService{publicfunctionmarkAsPaid(Order$order):void{// Will trigger notification$order->state->transitionTo(PaidState::class);// Also can be specified explicitly$order->state->transitionTo(PaidState::class,false);
    }publicfunctionmarkAsPaidSilently(Order$order):void{// Will not trigger notification$order->state->transitionTo(PaidState::class,true);
    }
}
```

Important notes:

- Custom parameters are only available within the context of the event listeners
- Parameters must be serializable if you plan to queue your state change listeners

Transition events

State scopes

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