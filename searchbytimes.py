# -*- coding: utf-8 -*-

PLUGIN_NAME = u"Search by Times"
PLUGIN_AUTHOR = u"Faux"
PLUGIN_DESCRIPTION = ""
PLUGIN_VERSION = "0.1"
PLUGIN_API_VERSIONS = ["0.9.0"]


from picard.cluster import Cluster
from picard.util import webbrowser2
from picard.ui.itemviews import BaseAction, register_cluster_action


class SearchByTimes(BaseAction):
	NAME = "Lookup by times..."

	def callback(self, objs):
		if len(objs) != 1 or not isinstance(objs[0], Cluster):
			return
		cluster = objs[0]

		url = "http://faux.no-ip.biz/mbreports/cueread2.php?"

		for i, file in enumerate(cluster.files):
			url += "%s=%s&" % (file.metadata["tracknumber"], file.metadata.length)

		webbrowser2.open(url)

register_cluster_action(SearchByTimes())
