from rest_framework.views import APIView
from rest_framework.response import Response


class MilestoneListView(APIView):
    def get(self, request):
        return Response([])


class DeadlineListView(APIView):
    def get(self, request):
        return Response([])


class ScrapedDeadlineListView(APIView):
    def get(self, request):
        return Response([])


class EvaluationListView(APIView):
    def get(self, request):
        return Response([])


class NotificationListView(APIView):
    def get(self, request):
        return Response([])
