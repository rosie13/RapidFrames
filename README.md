#RapidFrames

##What is it?
A lightweight prototype production framework for modelling the structure, design & UX of web and mobile sites.


##Features

- CSV as Database
- Pretty URL's 
- Custom Routing `routes.routed/template = 'this-should-route-to/routed/template.php'`
- Clever layouts
	- Access to page objects `$this->title`, `$this->ancestors`
	- Optionally load additional XML content for each page
	- Autoloading of header and footer blocks. You don't need to include `$this->getBlock('header')` in each layout. 
- Repeatable Blocks

	`<!--start:rf-repeat-->`

        <tr>
          <td>{{slug}}</td>
          <td>{{title}}</td>
          <td>{{template}}</td>
          <td>{{order}}</td>
          <td>
              <a href="/{{slug}}"><i class="icon-pencil"></i></a>
              <a href="#myModal" role="button" data-toggle="modal"><i class="icon-remove"></i></a>
          </td>
        </tr>
	`<!--end:rf-repeat-->`

- API interface using Smrtr-DataGrid to easily query any CSV and retrieve results in various supported formats:

	1. xml
	2. csv
	3. json


##Requirements
- PHP5.3+
- curl extension installed (optional)

##Installation
1. **git clone** 	https://github.com/mwayi/RapidFrames.git
2. Set Vhosts should point to RapidFrames/Public


		ServerName  rapidframes.local
        DocumentRoot /path/to/project/RapidFrames/Public

	