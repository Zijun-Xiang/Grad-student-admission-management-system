from rest_framework import serializers
from .models import Term, TermCourse
from courses.serializers import CourseSerializer


class TermSerializer(serializers.ModelSerializer):
    courses = serializers.SerializerMethodField()

    class Meta:
        model = Term
        fields = ["id", "student", "name", "order", "courses"]

    def get_courses(self, term):
        term_courses = TermCourse.objects.filter(term=term)
        return [
            CourseSerializer(tc.course).data
            for tc in term_courses
        ]
