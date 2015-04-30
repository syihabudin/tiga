{% extends 'section.tpl' %}


{% block title %}Userlist | {{ block.super }}{% endblock %}

{% block content %}
<h2>Userlist</h2>
<ul>
{% for user in users %}
  <li><a href="/users/{{ user.username|urlencode|escape }}">{{ user.username|escape }}</a></li>

  {% if not user.username == 'foobar' %}
      haha
  {% endif %}

{% endfor %}
</ul>
{% endblock %}