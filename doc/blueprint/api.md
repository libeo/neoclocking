FORMAT: 1A
HOST: /api

# Neoclocking

::: note
## Authorization Token
All requests must declare the user authorization token in the headers.

```http
X-Authorization: 3181ce3fb947d38699d00c0cecd7cf22e00d0e6d9b8c
```

If no authorization token or invalid token is sent, the response will be:

```json
{
    "message": "401 Unauthorized",
    "status_code": 401
}
```

## Retrieve the token [POST] [/user-auth]

Use this method to fetch your api key used for all calls.

+ Parameters
    + username: `jdoe` (string, required) - your ldap username
    + password: `1qazxsw2` (string, required) - your ldap password

+ Request (application/json)
    + Body
    
            {
                "username" : "jdoe",
                "password" : "1qazxsw2",
            }
            
+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "api_key" : "your-magical-lyrical-wunderful-apifull-apikeyfull" 
            }

:::

::: note
## Datetimes
All datetimes need to be sent for the timezone UTC. The api output datetimes only in UTC too.
:::

# Group Projects

# Projects [/projects/{projectNumber}{?term}]

## List Projects [GET]

Fetch 25 results.

+ Parameters
    + term: `term` (string, optional) - The term to filter the projects

+ Response 200 (application/json; charset=utf-8)
    + Body

            {
                "data": [
                    {
                        "id": 1,
                        "number": "P-99999-01",
                        "active": true,
                        "max_time": 60,
                        "remaining_time": 60,
                        "require_comments": true,
                        "name": "Project name",
                        "type": "Banque d'heures",
                        "should_not_exceed" : false,
                        "client": {
                            "data": {
                                "id": 1,
                                "name": "Libeo",
                                "number": 1
                            }
                        }
                    },
                    ...
                ]
            }
            
## Get a Project [GET /projects/{projectNumber}]

+ Parameters
    + projectNumber: `P-999999-01` (string, required) - The project number

+ Response 200 (application/json; charset=utf-8)
    + Body

            {
                "data": {
                    "id": 1,
                    "number": "P-99999-01",
                    "active": true,
                    "max_time": 60,
                    "remaining_time": 60,
                    "require_comments": true,
                    "name": "Project name",
                    "type": "Banque d'heures",
                    "should_not_exceed" : false,
                    "client": {
                        "data": {
                            "id": 1,
                            "name": "Libeo",
                            "number": 1
                        }
                    }
                }
            }

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "You don't have access to the project P-99999-01.",
                "status_code": 403
            }

+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "404 Not Found",
                "status_code": 404
            }


## Get all Projects [/projects/lists]

Fetch all projects the current user has access to.

+ Response 200 (application/json; charset=utf-8)
    + Body
        
            {
                "Client Name": [
                    {
                        "id": 1,
                        "number": "P-1-1",
                        "name": "Project Name",
                        "client_id": 1,
                        "active": true,
                        "max_time": 0,
                        "require_comments": false,
                        "created_at": "2016-01-01 00:00:00",
                        "updated_at": "2016-01-01 00:00:00",
                        "type": null
                    },
                    ...
                ],
                ...
            }

# Group Project tasks

# Tasks in a project [/projects/{projectNumber}/tasks{?start,length}]

## List tasks in a project [GET]

Endpoint to use with [DataTables jQuery plugin](https://datatables.net/).

+ Parameters
    + projectNumber: `P-999999-01` (string, required) - The project number
    + start : 10 (integer, optional) - Offset of the query
    + length : 10 (integer, optional) - How many results by page
    
+ Response 200 (application/json; charset=utf-8)
    + Body

            {
                "draw": 0,
                "recordsTotal": 10,
                "recordsFiltered": 10,
                "data": [
                    {
                        "id": 1,
                        "number": 1,
                        "name": "Task name",
                        "project_id": 1,
                        "resource_type_id": 1,
                        "reference_type_id": null,
                        "reference_number": null,
                        "estimation": 60,
                        "revised_estimation": 0,
                        "require_comments": false,
                        "created_at": "2014-12-19 11:59:14",
                        "updated_at": "2016-04-21 08:50:03",
                        "clocking_id": 1,
                        "logged_time": 120,
                        "milestone_id": null,
                        "active": true,
                        "DT_RowId": 1,
                        "is_active": "true",
                        "resource_type": "Autre/Legacy",
                        "milestone": null
                    },
                    ...
                ]
            }

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "You don't have access to the project P-99999-01.",
                "status_code": 403
            }

+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "404 Not Found",
                "status_code": 404
            }

## Update or create tasks in a project [PUT]

To create a task, set the `id` if a task to `new_` with a unique id in the request and it will be saved as a new task.

+ Parameters
    + projectNumber: `P-999999-01` (string, required) - The project number

+ Request JSON (application/json)
    + Body

            {
                "tasks" : {
                    "new_1" : {
                        "name" : "Task name 0",
                        "revised_estimation":"6:00",
                        "resource_type_id":5,
                        "require_comments":false,
                        "milestone_id":null
                    },
                    "1" : {
                        "name" : "Task name",
                        "revised_estimation":"6:00",
                        "resource_type_id":5,
                        "require_comments":false,
                        "milestone_id":null
                    },
                    "2" : {
                        "name" : "Task name 2",
                        "revised_estimation":"12:00",
                        "resource_type_id":4,
                        "require_comments":true,
                        "milestone_id":1
                    }
                }
            }

+ Request Form (application/x-www-form-urlencoded)
    + Body

            tasks[new_1][name]:Task Name 0
            tasks[new_1][revised_estimation]:6:00
            tasks[new_1][resource_type_id]:5
            tasks[new_1][require_comments]:0
            tasks[new_1][milestone_id]:
            tasks[1][name]:Task Name
            tasks[1][revised_estimation]:6:00
            tasks[1][resource_type_id]:5
            tasks[1][require_comments]:0
            tasks[1][milestone_id]:
            tasks[2][name]:Task Name 2
            tasks[2][revised_estimation]:12:00
            tasks[2][resource_type_id]:4
            tasks[2][require_comments]:1
            tasks[2][milestone_id]:1
            

+ Response 200 (application/json; charset=utf-8)

+ Response 400 (application/json; charset=utf-8)
    + Body
    
            {
              "message": "The request need to contain `tasks`.",
              "status_code": 400
            }

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "You don't have the rights to manage the project P-99999-01.",
                "status_code": 403
            }

+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "404 Not Found",
                "status_code": 404
            }

+ Response 422 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "Some tasks could not be saved.",
                "errors": {
                    "new_X": {
                        "name": [
                            "Le champ Nom de la tâche est obligatoire."
                        ],
                        "resource_type_id": [
                            "Le champ Type de ressource est obligatoire."
                        ]
                    },
                    ...
                },
                "status_code": 422
            }
            
## Delete tasks in a project [DELETE]

If one of the task can't be delete, none are deleted.

+ Parameters
    + projectNumber: `P-999999-01` (string, required) - The project number
    
+ Request (application/json)
    + Body
    
            {
                "ids" : [
                    "1",
                    "2"
                ]
            }

+ Response 200 (application/json; charset=utf-8)

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "You don't have the rights to manage the project P-99999-01.",
                "status_code": 403
            }

+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "404 Not Found",
                "status_code": 404
            }
            
# Group Milestones

# Milestones in a project [/projects/{projectNumber}/milestone]

## List milestones in a project [GET]

+ Parameters
    + projectNumber: `P-999999-01` (string, required) - The project number
            
+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": [
                    {
                        "id": 1,
                        "name": "Milestone name 0"
                    },
                    {
                        "id": 2,
                        "name": "Milestone name 1"
                    },
                    ...
                ]
            }

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "You don't have the rights to manage the project P-99999-01.",
                "status_code": 403
            }

+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "404 Not Found",
                "status_code": 404
            }

## Create a milestone in a project [POST]

+ Parameters
    + projectNumber: `P-999999-01` (string, required) - The project number

+ Request (application/json)
    + Body
    
            {
                "milestone_name" : "Milestone name 1"
            }
            
+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "id": 2,
                "milestones": {
                    "1": "Milestone name 0",
                    "2": "Milestone name 1",
                    ...
                }
            }

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "You don't have the rights to manage the project P-99999-01.",
                "status_code": 403
            }

+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "404 Not Found",
                "status_code": 404
            }

+ Response 422 (application/json; charset=utf-8)
    + Body
            
            {
                "message": "Could not save the milestone.",
                "errors": {
                    "milestone_name": [
                        "The milestone name is already used."
                    ],
                    ...
                },
                "status_code": 422
            }

# Group Tasks

# Tasks [/tasks{?term}]

## List tasks [GET]

+ Parameters
    + term: `term` (string, required) - Term to filter the task against

+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": [
                    {
                        "id": 1,
                        "number": 1,
                        "name": "Task name",
                        "estimation": 480,
                        "reference_number": null,
                        "revised_estimation": 0,
                        "logged_time": 255,
                        "estimation_exceeded": false,
                        "active": true,
                        "favourited": false,
                        "user_can_edit": false,
                        "require_comments": false,
                        "resource": {
                            "data": {
                                "id": 1,
                                "code": "autre",
                                "name": "Autre/Legacy"
                            }
                        },
                        "reference": {
                            "data": {
                                "id": 1,
                                "code": "redmine",
                                "name": "Redmine",
                                "prefix": "https://projets.libeo.com/issues/"
                            }
                        },
                        "project": {
                            "data": {
                                "id": 1,
                                "number": "P-999999-01",
                                "active": true,
                                "max_time": 0,
                                "remaining_time": 60,
                                "require_comments": false,
                                "name": "Project name",
                                "type": "Banque d'heures",
                                "should_not_exceed" : false,
                                "client": {
                                    "data": {
                                        "id": 1,
                                        "name": "Client name",
                                        "number": 1
                                    }
                                }
                            }
                        },
                        "client": {
                            "data": {
                                "id": 1,
                                "name": "Client name",
                                "number": 1
                            }
                        }
                    },
                    ...
                ]
            }

+ Response 400 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "The parameter `term` is required.",
                "status_code": 400
            }
            

## Get a task [GET /tasks/{taskNumber}]

+ Parameters
    + taskNumber: `9999` (integer, required) - Number of the task

+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": {
                    "id": 1,
                    "number": 1,
                    "name": "Task name",
                    "estimation": 480,
                    "reference_number": null,
                    "revised_estimation": 0,
                    "logged_time": 255,
                    "estimation_exceeded": false,
                    "active": true,
                    "favourited": false,
                    "user_can_edit": false,
                    "require_comments": false,
                    "resource": {
                        "data": {
                            "id": 1,
                            "code": "autre",
                            "name": "Autre/Legacy"
                        }
                    },
                    "reference": {
                        "data": {
                            "id": 1,
                            "code": "redmine",
                            "name": "Redmine",
                            "prefix": "https://projets.libeo.com/issues/"
                        }
                    },
                    "project": {
                        "data": {
                            "id": 1,
                            "number": "P-999999-01",
                            "active": true,
                            "max_time": 0,
                            "remaining_time": 60,
                            "require_comments": false,
                            "name": "Project name",
                            "type": "Banque d'heures",
                            "should_not_exceed" : false,
                            "client": {
                                "data": {
                                    "id": 1,
                                    "name": "Client name",
                                    "number": 1
                                }
                            }
                        }
                    }
                }
            }
            
+ Response 400 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "Le numéro de tâche n'est pas valide.",
                "status_code": 404
            }
          
+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "Vous n'avez pas accès à la tâche #999999",
                "status_code": 403
            }
            
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "La tâche #999999 n'existe pas.",
                "status_code": 404
            }

## Update task [PATCH /tasks/{taskNumber}]

+ Parameters
    + taskNumber: `9999` (integer, required) - Number of the task

+ Request (application/json)
    + Body
    
            {
                "name" : "New task name",
                "project_id" => 2,
                "active":false,
                "resource_type_id" : 1,
                "reference_type_id" : 1,
                "reference_number" : "2222",
                "revised_estimation" : 50:00,
                "require_comments" : true,
                "milestone_id": 2
            }

+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": {
                    "id": 1,
                    "number": 1,
                    "name": "Task name",
                    "estimation": 480,
                    "reference_number": null,
                    "revised_estimation": 0,
                    "logged_time": 255,
                    "estimation_exceeded": false,
                    "active": true,
                    "favourited": false,
                    "user_can_edit": false,
                    "require_comments": false,
                    "resource": {
                        "data": {
                            "id": 1,
                            "code": "autre",
                            "name": "Autre/Legacy"
                        }
                    },
                    "reference": {
                        "data": {
                            "id": 1,
                            "code": "redmine",
                            "name": "Redmine",
                            "prefix": "https://projets.libeo.com/issues/"
                        }
                    },
                    "project": {
                        "data": {
                            "id": 1,
                            "number": "P-999999-01",
                            "active": true,
                            "max_time": 0,
                            "remaining_time": 60,
                            "require_comments": false,
                            "name": "Project name",
                            "type": "Banque d'heures",
                            "should_not_exceed" : false,
                            "client": {
                                "data": {
                                    "id": 1,
                                    "name": "Client name",
                                    "number": 1
                                }
                            }
                        }
                    }
                }
            }
               
+ Response 400 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "Le numéro de tâche n'est pas valide.",
                "status_code": 404
            }

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "403 Forbidden", 
                "status_code": 403
            }

## Delete task [DELETE /tasks/{taskNumber}]

+ Parameters
    + taskNumber: `9999` (integer, required) - Number of the task

+ Response 200 (application/json; charset=utf-8)
      
+ Response 400 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "Le numéro de tâche n'est pas valide.",
                "status_code": 404
            }

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "403 Forbidden", 
                "status_code": 403
            }

# Group Live entries

# Live entries [/live-entries]

## Get live entry [GET]

+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": {
                    "started_at": {
                        "date": "2016-05-05 12:56:00.000000",
                        "timezone_type": 3,
                        "timezone": "America/Montreal"
                    },
                    "comment": "",
                    "task": {
                        "data": {
                            "id": 1,
                            "number": 1,
                            "name": "Task name",
                            "estimation": 240,
                            "reference_number": null,
                            "revised_estimation": 0,
                            "logged_time": 170,
                            "estimation_exceeded": false,
                            "active": true,
                            "favourited": true,
                            "user_can_edit": true,
                            "require_comments": false,
                            "resource": {
                                "data": {
                                    "id": 1,
                                    "code": "autre",
                                    "name": "Autre/Legacy"
                                }
                            },
                            "project": {
                                "data": {
                                    "id": 1,
                                    "number": "P-999999-01",
                                    "active": true,
                                    "max_time": 120000,
                                    "remaining_time": 60,
                                    "require_comments": false,
                                    "name": "Project name",
                                    "type": "Banque d'heures",
                                    "should_not_exceed" : false,
                                    "client": {
                                        "data": {
                                            "id": 1,
                                            "name": "Client name",
                                            "number": 1
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

## Create live entry [POST]

Can create a live entry only if none exists.

+ Request (application/json)
    + Body
    
            {
                "task_id":1,
                "started_at":"2016-05-05 13:03:00",
                "comment":""
            }
             
+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": {
                    "started_at": {
                        "date": "2016-05-05 12:56:00.000000",
                        "timezone_type": 3,
                        "timezone": "America/Montreal"
                    },
                    "comment": "",
                    "task": {
                        "data": {
                            "id": 1,
                            "number": 1,
                            "name": "Task name",
                            "estimation": 240,
                            "reference_number": null,
                            "revised_estimation": 0,
                            "logged_time": 170,
                            "estimation_exceeded": false,
                            "active": true,
                            "favourited": true,
                            "user_can_edit": true,
                            "require_comments": false,
                            "resource": {
                                "data": {
                                    "id": 1,
                                    "code": "autre",
                                    "name": "Autre/Legacy"
                                }
                            },
                            "project": {
                                "data": {
                                    "id": 1,
                                    "number": "P-999999-01",
                                    "active": true,
                                    "max_time": 120000,
                                    "remaining_time": 60,
                                    "require_comments": false,
                                    "name": "Project name",
                                    "type": "Banque d'heures",
                                    "should_not_exceed" : false,
                                    "client": {
                                        "data": {
                                            "id": 1,
                                            "name": "Client name",
                                            "number": 1
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
             
+ Response 403 (application/json; charset=utf-8)
    + Body  
            
            {
              "message": "Vous n'avez pas accès à la tâche #999",
              "status_code": 403
            }
             
+ Response 404 (application/json; charset=utf-8)
    + Body           
    
            {
                "message": "La tâche 1 n'existe pas.",
                "status_code": 404
            }
            
+ Response 409 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "A live clocking session is already running.",
                "status_code": 409
            }

+ Response 422 (application/json; charset=utf-8)
    + Body
            
            {
                "message": "Could not save the live entry.",
                "errors": {
                    "task_id": [
                        "The task is required."
                    ],
                    "started_at": [
                        "The start time is required."
                    ],
                    ...
                },
                "status_code": 422
            }
            
## Update live entry [PATCH]

Only the comment of the live entry can be updated.

+ Request (application/json)
    + Body
    
            {
                "comment":"My comment"
            }
            
+ Response 200 (application/json; charset=utf-8)
            
+ Response 409 (application/json; charset=utf-8)
    + Body
    
            {
                  "message": "No live clocking session running.",
                  "status_code": 410
            }

## Delete live entry [DELETE]

Will just do nothing if no live entry are present.

+ Response 200 (application/json; charset=utf-8)

# Group Users

# Users [/users]

## List users [GET]

+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data" : [
                    {
                        "id": 1,
                        "username": "username",
                        "mail": "username@libeo.com",
                        "active": true,
                        "first_name": "First",
                        "last_name": "Name",
                        "week_duration": 2400,
                        "hourly_cost": 8000,
                        "fullname": "First Name",
                        "gravatar": "https://www.gravatar.com/avatar/1511af0156826e609f7d594bcf35f4cf?d=mm"
                    },
                    ...
                ]
            }

# Get a user [GET /users/{username}]

+ Parameters
    + username: `fname` (integer, required) - Username of the user
    
+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data" : {
                    "id": 1,
                    "username": "username",
                    "mail": "username@libeo.com",
                    "active": true,
                    "first_name": "First",
                    "last_name": "Name",
                    "week_duration": 2400,
                    "hourly_cost": 8000,
                    "fullname": "First Name",
                    "gravatar": "https://www.gravatar.com/avatar/1511af0156826e609f7d594bcf35f4cf?d=mm"
                }
            }
    
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "Could not find user \"fname\".",
                "status_code": 404
            }
    
# Get a user worked times [GET /users/{username}/workedTime]

+ Parameters
    + username: `fname` (integer, required) - Username of the user
    
+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "duration_week": 2400,
                "duration_day": 480,
                "time_worked_this_week": 335,
                "time_worked_today": 40
            }

+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "Could not find user \"fname\".",
                "status_code": 404
            }

# Get a user remaining time for the week [GET /users/{username}/timeRemainingThisWeek]

+ Parameters
    + username: `fname` (integer, required) - Username of the user
       
+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": {
                    "time_remaining": 2065
                }
            }
 
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "Could not find user \"fname\".",
                "status_code": 404
            }

# Group Log entries

# Log entries [/log-entries/{?filterBy,date}]

# List log entries [GET]

Log entries, most recent to oldest.

+ Parameters
    + filterBy: `day` (string, optional) - Filter to use: `day` or `week`
    + date: `2016-04-30` (string, optional) - Date to filter with. Use today by default. Used only if `filterBy` is set.

+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": [
                    {
                        "id": 1,
                        "started_at": "2016-05-05 13:03:00",
                        "ended_at": "2016-05-05 13:43:00",
                        "validated": false,
                        "hourly_cost": 80,
                        "comment": "",
                        "duration": 40,
                        "can_be_deleted": true,
                        "can_be_edited": true,
                        "task": {
                            "data": {
                                "id": 1,
                                "number": 1,
                                "name": "Task name",
                                "estimation": 240,
                                "reference_number": null,
                                "revised_estimation": 0,
                                "logged_time": 210,
                                "estimation_exceeded": false,
                                "active": true,
                                "favourited": true,
                                "user_can_edit": true,
                                "require_comments": false,
                                "resource": {
                                    "data": {
                                        "id": 1,
                                        "code": "autre",
                                        "name": "Autre/Legacy"
                                    }
                                }
                            }
                        },
                        "project": {
                            "data": {
                                "id": 1,
                                "number": "P-999999-03",
                                "active": true,
                                "max_time": 120000,
                                "remaining_time": 60,
                                "require_comments": false,
                                "name": "Project Name",
                                "type": "Banque d'heures",
                                "should_not_exceed" : false,
                                "client": {
                                    "data": {
                                        "id": 1,
                                        "name": "Client name",
                                        "number": 1
                                    }
                                }
                            }
                        },
                        "client": {
                            "data": {
                                "id": 1,
                                "name": "Client name",
                                "number": 1
                            }
                        }
                    },
                    ...
                ]
            }
 
+ Response 400 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "The filterBy parameter must be `week` or `day`.",
                "status_code": 400
            }

# Get a log entry [GET /log-entries/{id}]
 
+ Parameters
    + id: `999` (string, required) - Id of the log entry.

+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": {
                    "id": 1,
                    "started_at": "2016-05-05 13:03:00",
                    "ended_at": "2016-05-05 13:43:00",
                    "validated": false,
                    "hourly_cost": 80,
                    "comment": "",
                    "duration": 40,
                    "can_be_deleted": true,
                    "can_be_edited": true,
                    "task": {
                        "data": {
                            "id": 1,
                            "number": 1,
                            "name": "Task name",
                            "estimation": 240,
                            "reference_number": null,
                            "revised_estimation": 0,
                            "logged_time": 210,
                            "estimation_exceeded": false,
                            "active": true,
                            "favourited": true,
                            "user_can_edit": true,
                            "require_comments": false,
                            "resource": {
                                "data": {
                                    "id": 1,
                                    "code": "autre",
                                    "name": "Autre/Legacy"
                                }
                            }
                        }
                    },
                    "project": {
                        "data": {
                            "id": 1,
                            "number": "P-999999-03",
                            "active": true,
                            "max_time": 120000,
                            "remaining_time": 60,
                            "require_comments": false,
                            "name": "Project Name",
                            "type": "Banque d'heures",
                            "should_not_exceed" : false,
                            "client": {
                                "data": {
                                    "id": 1,
                                    "name": "Client name",
                                    "number": 1
                                }
                            }
                        }
                    },
                    "client": {
                        "data": {
                            "id": 1,
                            "name": "Client name",
                            "number": 1
                        }
                    }
                }
            }
 
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
              "message": "404 Not Found",
              "status_code": 404
            }

# Create a log entry [POST]

+ Request (application/json)
    + Body
    
            {
                "task_id":1,
                "started_at":"2016-05-05 13:00:00",
                "ended_at":"2016-05-05 15:00:00",
                "comment":""
            }

+ Response 200 (application/json; charset=utf-8)
    + Body
            
            {
                "data": {
                    "id": 1,
                    "started_at": "2016-05-05 13:03:00",
                    "ended_at": "2016-05-05 13:43:00",
                    "validated": false,
                    "hourly_cost": 80,
                    "comment": "",
                    "duration": 40,
                    "can_be_deleted": true,
                    "can_be_edited": true,
                    "task": {
                        "data": {
                            "id": 1,
                            "number": 1,
                            "name": "Task name",
                            "estimation": 240,
                            "reference_number": null,
                            "revised_estimation": 0,
                            "logged_time": 210,
                            "estimation_exceeded": false,
                            "active": true,
                            "favourited": true,
                            "user_can_edit": true,
                            "require_comments": false,
                            "resource": {
                                "data": {
                                    "id": 1,
                                    "code": "autre",
                                    "name": "Autre/Legacy"
                                }
                            }
                        }
                    },
                    "project": {
                        "data": {
                            "id": 1,
                            "number": "P-999999-03",
                            "active": true,
                            "max_time": 120000,
                            "remaining_time": 60,
                            "require_comments": false,
                            "name": "Project Name",
                            "type": "Banque d'heures",
                            "should_not_exceed" : false,
                            "client": {
                                "data": {
                                    "id": 1,
                                    "name": "Client name",
                                    "number": 1
                                }
                            }
                        }
                    },
                    "client": {
                        "data": {
                            "id": 1,
                            "name": "Client name",
                            "number": 1
                        }
                    }
                }
            }
            
+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "403 Forbidden",
                "status_code": 403
            }
            
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "The task #9999 do not exists.",
                "status_code": 404
            }

+ Response 422 (application/json; charset=utf-8)
    + Body
            
            {
                "message": "422 Unprocessable Entity",
                "errors": {
                    "ended_at": [
                        "Le champ ended at est obligatoire."
                    ],
                    ...
                },
                "status_code": 422
            }
 

# Update a log entry [PATCH]

+ Request (application/json)
    + Body
    
            {
                "task_id":1,
                "started_at":"2016-05-05 13:00:00",
                "ended_at":"2016-05-05 15:00:00",
                "comment":""
            }

+ Response 200 (application/json; charset=utf-8)
    + Body
            
            {
                "data": {
                    "id": 1,
                    "started_at": "2016-05-05 13:03:00",
                    "ended_at": "2016-05-05 13:43:00",
                    "validated": false,
                    "hourly_cost": 80,
                    "comment": "",
                    "duration": 40,
                    "can_be_deleted": true,
                    "can_be_edited": true,
                    "task": {
                        "data": {
                            "id": 1,
                            "number": 1,
                            "name": "Task name",
                            "estimation": 240,
                            "reference_number": null,
                            "revised_estimation": 0,
                            "logged_time": 210,
                            "estimation_exceeded": false,
                            "active": true,
                            "favourited": true,
                            "user_can_edit": true,
                            "require_comments": false,
                            "resource": {
                                "data": {
                                    "id": 1,
                                    "code": "autre",
                                    "name": "Autre/Legacy"
                                }
                            }
                        }
                    },
                    "project": {
                        "data": {
                            "id": 1,
                            "number": "P-999999-03",
                            "active": true,
                            "max_time": 120000,
                            "remaining_time": 60,
                            "require_comments": false,
                            "name": "Project Name",
                            "type": "Banque d'heures",
                            "should_not_exceed" : false,
                            "client": {
                                "data": {
                                    "id": 1,
                                    "name": "Client name",
                                    "number": 1
                                }
                            }
                        }
                    },
                    "client": {
                        "data": {
                            "id": 1,
                            "name": "Client name",
                            "number": 1
                        }
                    }
                }
            }
            
+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "403 Forbidden",
                "status_code": 403
            }
            
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "The task #9999 do not exists.",
                "status_code": 404
            }

+ Response 422 (application/json; charset=utf-8)
    + Body
            
            {
                "message": "422 Unprocessable Entity",
                "errors": {
                    "ended_at": [
                        "Le champ ended at est obligatoire."
                    ],
                    ...
                },
                "status_code": 422
            }
 

# Delete a log entry [DELETE]

+ Response 200 (application/json; charset=utf-8)

+ Response 403 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "403 Forbidden",
                "status_code": 403
            }
            
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "404 Not Found",
                "status_code": 404
            }

# Group Favourite tasks

# Favourite tasks [/favourite-tasks]

# List favourite tasks [GET]

+ Response 200 (application/json; charset=utf-8)
    + Body
    
            {
                "data": [
                    {
                        "id": 1,
                        "number": 1,
                        "name": "Task name",
                        "estimation": 480,
                        "reference_number": null,
                        "revised_estimation": 0,
                        "logged_time": 255,
                        "estimation_exceeded": false,
                        "active": true,
                        "favourited": false,
                        "user_can_edit": false,
                        "require_comments": false,
                        "resource": {
                            "data": {
                                "id": 1,
                                "code": "autre",
                                "name": "Autre/Legacy"
                            }
                        },
                        "project": {
                            "data": {
                                "id": 1,
                                "number": "P-999999-01",
                                "active": true,
                                "max_time": 0,
                                "remaining_time": 60,
                                "require_comments": false,
                                "name": "Project name",
                                "type": "Banque d'heures",
                                "should_not_exceed" : false,
                                "client": {
                                    "data": {
                                        "id": 1,
                                        "name": "Client name",
                                        "number": 1
                                    }
                                }
                            }
                        },
                        "client": {
                            "data": {
                                "id": 1,
                                "name": "Client name",
                                "number": 1
                            }
                        }
                    },
                    ...
                ]
            }

# Add a favourite tasks [POST]

+ Request (application/json)
    + Body
    
            {
                "number":1
            }

+ Response 200 (application/json; charset=utf-8)

+ Response 400 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "The `number` parameter is required.",
                "status_code": 400
            }
            
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "The task 1 do not exists.",
                "status_code": 404
            }


# Delete a favourite tasks [DELETE]

+ Request (application/json)
    + Body
    
            {
                "number":1
            }
            
+ Response 200 (application/json; charset=utf-8)

+ Response 400 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "The `number` parameter is required.",
                "status_code": 400
            }
            
+ Response 404 (application/json; charset=utf-8)
    + Body
    
            {
                "message": "The task 1 do not exists.",
                "status_code": 404
            }

# Group Reference types

# Reference types [/reference-types]

## List reference types [GET]

+ Response 200 (application/json; charset=utf-8)
    + Body
            
            {
              "data": [
                {
                  "id": 1,
                  "code": "redmine",
                  "name": "Redmine",
                  "prefix": "https://projets.libeo.com/issues/"
                }
              ]
            }

# Group Resource types

# Resource types [/resource-types]

## List resource types [GET]

+ Response 200 (application/json; charset=utf-8)
    + Body
            
            {
                "data": [
                    {
                        "id": 1,
                        "code": "autre",
                        "name": "Autre/Legacy",
                        "children" : {
                            "data" : []
                        }
                    },
                    {
                        "id": 2,
                        "code": "formation",
                        "name": "Formation",
                        "children" : {
                            "data" : [
                                "id": 3,
                                "code": "ressource",
                                "name": "Ressource name",
                                "children" : {
                                    "data" : []
                                }
                            ]
                        }
                    },
                      ...
                ]
            }
            
