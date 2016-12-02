
# docteurklein/crud-bundle

## What?

A symfony bundle to ease creation of CRUD operations.

It will create format-agnostic routes that point (by default) to a generic CRUD controller.  
This controller uses FosRestBundle views to render either json-hal responses or html twig templates that both
exploit willdurand/hateoas metadata.

## How?

Add a route entry in your application routing:

```yaml
users:
    type: docteurklein.crud
    resource: admin@users?class=App:User
```

