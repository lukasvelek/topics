# Developer documentation for topics
## Content
1. Different sections of the application
    1. Authenticators
    2. Authorizators
    3. Components
    4. Constants
    5. Core
    6. Entities
    7. Exceptions
    8. Helpers
    9. Managers
    10. Modules
    11. Repositories
    12. Services
    13. UI
2. UI / Frontend
3. Backend
4. Background services
5. Logging
6. Caching
7. Asynchronous server requests (AJAX)
8. Application life cycle

## 1 Different sections of the application
The application has different sections of code. All the important code is located in the `app/` directory.

### 1.1 Authenticators
Authenticators are located in `app/authenticators`. The only used authenticator is the `UserAuthenticator` that allows users to login and also to perform certain tasks, where authentication of the user is needed.

The `UserAuthenticator` has several methods. E.g. `loginUser()` is used in the login form, when user tries to login. It checks if the username and password entered are correct and equal to information saved in the database. Documentation for other methods can be found in the class itself.

### 1.2 Authorizators
Authorizators are located in `app/authorizators`. Their job is to check if user is authorized to perform or see certain things.

Currently three different authorizators are used - `ActionAuthorizator`, `SidebarAuthorizator` and `VisibilityAuthorizator`. All these three authorizators extend an abstract `AAuthorizator` that contains common methods.

`ActionAuthorizator` is responsible for checking if user is allowed to perform certain actions - e.g. delete posts, create polls, manage topic users, etc.

`SidebarAuthorizator` is responsible for checking if user is allowed to view different sections of sidebar that is used in the management section of the application.

`VisibilityAuthorizator` is responsible for checking if user is allowed to view deleted posts, topics and private topics.

### 1.3 Components
Components are in context of this application self-standing UI elements. They are located in `app/components`.

There are currently three components - `Navbar`, `PostLister` and `Sidebar`.

`Navbar` is used throughout the whole application and it is the upper bar with links leading to different sections.

`PostLister` is used to display posts on the homepage.

`Sidebar` is used similarly to `Navbar` but is displayed on the side and is only visible in the management section.

### 1.4 Constants
`Constants` section contains classes with constants. They are located in `app/constants`.

### 1.5 Core
`Core` section contains core classes as well as the superior `Application` class that is responsible for running the application itself. The core is located in `app/core`.

`Datatypes` (`app/core/datatypes`) contains classes that represent custom made data types. Currently there is only `ArrayList` that acts as an array but it contains advanced and useful functions.

`Datetypes` (`app/core/datetypes`) contains classes that represent date or time.

`QueryBuilder` (`app/core/QueryBuilder`) contains a source code of other application's author's other project. It allows to easily create SQL queries without writing SQL itself.

`Vendor` (`app/core/vendor`) contains vendor classes.

In `core` section are also classes responsible for handling files (`FileManager`), generating hashes (`HashManager`), handling caching (`CacheManager`), logging (`Logger`) and connecting to database and handling database related operations (`DatabaseConnection`).

For installing purposes there is also `DatabaseInstaller` that is reponsible for creating database tables and inserting necessary data. When the application is installed a file `install` is created. Once this file is deleted, the application will install again.

For UI rendering there is `RenderEngine` that is responsible for rendering the templates and their respective sections and displaying it to the user.

`ServiceManager` is responsible for running background tasks or in context of this applications 'services'.

### 1.6 Entities
`Entities` section contains classes that represent certain entities. Usually every database table has a entity class that represents a single row of data.

Each entity usually has private readonly attributes. Their values are set during construction of the object and their values can be obtain using `getXXX()` or `isXXX()` (for bool attributes) methods.

### 1.7 Exceptions
`Exceptions` section contains class that represent exceptions that can be thrown in sections of the application.

Each custom exception should extend abstract class `AException` that extends `Exception` class. `AException` contains useful methods as file saving etc.

### 1.8 Helpers
`Helpers` section contains (usually) static classes with static methods that help programmer. The help can be in terms of displaying data, formatting data and modifying data.

### 1.9 Managers
`Managers` section contains classes that allow modifying data in the database using repositories. In contradiction to repository classes, managers also check if user is allowed to perform certain action.

All managers should extends `AManager` abstract class that contains common methods.

### 1.10 Modules
`Modules` section contains classes that represent different sections of UI. Currently there are four sections - `AdminModule`, `AnonymModule`, `ErrorModule` and `UserModule`.

Each module is then divided into presenters and each presenter contains actions that user is allowed to perform.

Modules are dynamically loaded and thus only the used module is loaded. For development purposes the modules are also dynamically loaded and thus it is not needed to define them in a config file.

Each module must be placed in its coresponding directory and within the directory there must be a class with the name of the directory - e.g. `app/modules/UserModule/UserModule.php`. This class must extend the abstract class `AModule`. Then within the single module directory there must be a `Presenters` folder that contains the Presenter classes themselves. Each presenter class must extend the abstract class `APresenter`. If a presenter contains a `renderXXX()` method it must also have a coresponding HTML template file. These HTML template files are located in the `templates` folder located in the `Presenters` folder. In the `templates` folder a folder with the name of the presenter must exist and within the presenter folder (int the `templates` folder) must exist the HTML template with the name of the action.

E.g. In URL we have this: `?page=UserModule:Topics&action=profile&topicId=...` and that means that we are calling the `UserModule` module and the `Topics` presenter. The action called is `profile` and thus either `handleProfile` or `renderProfile` method in the `Topics` presenter must exist. If there is `renderProfile()` method defined, then the HTML template must exist and it should be `app/modules/UserModule/Presenters/templates/TopicsPresenter/profile.html`.

### 1.10.1 AdminModule
`AdminModule` is a UI section that is available only to administrators (in every context) or those users, who are able to access this section.

### 1.10.2 AnonymModule
`AnonymModule` is a UI section that is available for users, who have not logged in yet.

### 1.10.3 ErrorModule
`ErrorModule` is a UI section that is used when a fatal error occurs.

### 1.10.4 UserModule
`UserModule` is the most used UI section, because it contains everything that is visible to every user.

### 1.11 Repositories
`Repositories` are classes that extend the abstract class `ARepository` and that allow working with data in the database.

In most cases each repository operates on a single database table that's name is name of the repository. However sometimes a repository works with several database tables.

In contradiction to `Managers`, repositories do not check rights and thus should not be used without checking for user rights (where necessary).

### 1.12 Services
`Services` are classes that are used to be run in background and to perform certain set of operations.

### 1.13 UI
`UI` section contains UI components that are used within the application. E.g. `GridBuilder` that allows creating tables (or in context of this application - grids). There is also `FormBuilder` that allows creating forms.

An important class is also `LinkBuilder` that is used to create a `<a>` link in PHP easily.

## 2 UI / Frontend

## 3 Backend

## 4 Background services
Background services are classes that contain methods that perform time-taking operations.

Each background service contains of two parts - the running section and the class section.

The running section is located in the `services` directory in the root of the application and the class section is located in the `services` directory in the `app/` directory.

The running section is the script that loads required classes and creates an instance of the class section. Each class section implements the `IRunnable` interface and thus contains the `run()` method that performs all the operations. And therefore the running sections calls this method on the instance of the class section.

The class section is a class that contains all the methods required for the service to perform what it's intended to do. Every class section must extend the `AService` abstract class that implements the `IRenderable` interface.

All background processes are run in independent thread and thus it cannot interfere with the main thread the application runs in.

## 5 Logging
Logging is handled by the `Logger` class. Log files are located in the root of the application in `logs/` directory.

Logging has several levels and three namespaces.

The three namespaces are:
- Default (`log_`)
- Service (`service_log_`)
- SQL (`sql_log_`)

The levels define what is allowed to be logged - information, warnings, errors, exceptions, services, SQLs.

For each day in a month a new log file is created - no matter the namespace.

`Logger` class also allows stopwatching the actions passed in the callback. E.g. it can measure how much time does a operation take.

## 6 Caching
Caching is handled using the `CacheManager` class. Cache files are located in the root of the application in `cache/` directory.

Cache is divided into separate sections - namespaces. All the namespaces are defined as contants in the `CacheManager` class.

Cached data is loaded from the cache using `loadCache()` method. It requires the key of the element and the callback function that is used to obtain the result in case no value for the key exists. It also required the namespace. It may contain the name of the calling method and the expiration in form of an instance of `DateTime`.

The cached data can expire and when it does it is invalidated and cached again.

The cached data is saved in form of an serialized array. When loaded it gets unserialized and searches for the key passed. If the key does not exist, it adds the key with the result of the callback as an result to the array and finally saves it again.

If no data loading from cache is needed and only saving is required, then `saveCache` method comes in hand. It will only save data and return true if the operation was successful or false if not.

## 7 Asynchronous server requests (AJAX)
Asynchronous requests are handled using AJAX and in particular using the `jquery` JavaScript library.

These requests are not written manually and instead the `AjaxRequestBuilder` class is used. It allows creating a JS code using PHP.

To use `AjaxRequestBuilder` it's instance must be created. It requires these methods to be called:
- `setURL()`
    - Sets the URL of the PHP handler that will handle the request and return a JSON-encoded response
    - An array of parameters is passed
- `setMethod()`
    - Sets the method of the request
    - Most commonly `GET`
- `setHeader()`
    - Sets the request header parameters
    - If dynamic values from the JS function attributes are needed to be passed the underscore symbol (`_`) is used - e.g. `_page`
- `setFunctionName()`
    - Sets the JS function name
    - It is useful for calling the JS function
- `setFunctionArguments()`
    - Sets the JS function arguments
    - These should start with underscore symbol (`_`)
- `updateHtmlElement()`
    - When the request is finished and a response is returned it will insert (or append) to the HTML element

When the AJAX request is defined that it is passed to the template using `addScript()` method of `APresenter` abstract class.

When the JS code is created an additional parameter is passed to the URL - `isAjax=1`. This indicates to the presenter and render engine that it will return a JSON-encoded string.

An action in a presenter that handles AJAX request begins with `action` - e.g. `actionLoadData()`. It can obtain parameters passed from the URL query using `httpGet` method of `APresenter` abstract class.

## 8 Application life cycle