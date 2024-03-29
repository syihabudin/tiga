<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Frameset//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html>
  <head>
    <title>{% block title %}{% endblock %}</title>
    <style type="text/css">
      {% block style %}
      body {
        background-color: #333;
        color: #eee;
        font-family: 'Arial', sans-serif;
        font-size: 0.9em;
      }

      a {
        color: #d00;
      }
    {% endblock %}
    </style>
  </head>
  <body>
    {% block body %}
      <div id="header">
        <h1>My Webpage</h1>
        <h2>My Subtitle</h2>
      </div>

      <div id="navigation">
        <ul>
        {% block navigation %}
          <li><a href="index.html">My Home</a></li>
          <li><a href="downloads.html">Download</a></li>
          <li><a href="about.html">About us</a></li>
        {% endblock %}
        </ul>
      </div>

      <div id="content">
        {% block content %}{% endblock %}
      </div>
      <div id="footer">
        {% include 'shared/about.tpl' %}

        &copy; Copyright 2007 by Yourself
      </div>
    {% endblock %}
  </body>
</html>
