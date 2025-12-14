from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status
from django.shortcuts import get_object_or_404
from django.db import transaction

from .models import Term, TermCourse
from .serializers import TermSerializer

from students.models import Student
from courses.models import Course
from enrollments.models import Enrollment


class TermListView(APIView):
    def get(self, request, student_id):
        student = get_object_or_404(Student, pk=student_id)
        terms = Term.objects.filter(student=student).order_by("order")
        return Response(TermSerializer(terms, many=True).data)

    def post(self, request, student_id):
        student = get_object_or_404(Student, pk=student_id)

        name = request.data.get("name")
        max_order = Term.objects.filter(student=student).aggregate(models.Max("order"))["order__max"] or 0

        term = Term.objects.create(
            student=student,
            name=name or f"Term {max_order + 1}",
            order=max_order + 1,
        )

        return Response({
            "message": "Term created",
            "term": TermSerializer(term).data
        }, status=201)


class AddCourseToTermView(APIView):
    def post(self, request, student_id, term_id):
        student = get_object_or_404(Student, pk=student_id)
        term = get_object_or_404(Term, pk=term_id)

        if term.student != student:
            return Response({"message": "Term does not belong to student"}, status=403)

        course_id = request.data.get("course_id")
        course = get_object_or_404(Course, pk=course_id)

        # Check duplicate
        if TermCourse.objects.filter(term=term, course=course).exists():
            return Response({"message": "Course already planned in this term"}, status=400)

        # Completed courses
        completed = Enrollment.objects.filter(
            student=student,
            status__in=["completed"],
        ).values_list("course_id", flat=True)

        graded = Enrollment.objects.filter(
            student=student
        ).exclude(grade=None).values_list("course_id", flat=True)

        completed_ids = set(list(completed) + list(graded))

        # Earlier planned
        earlier = TermCourse.objects.filter(
            term__student=student,
            term__order__lt=term.order
        ).values_list("course_id", flat=True)

        available = set(list(completed_ids) + list(earlier))

        # Check prerequisites
        groups = course.prerequisite_groups.all().prefetch_related("prerequisites")

        if not groups.exists():
            TermCourse.objects.create(term=term, course=course)
            return Response({"message": "Course planned successfully"})

        missing_groups = []

        for g in groups:
            req_ids = list(g.prerequisites.values_list("id", flat=True))
            diff = [x for x in req_ids if x not in available]
            if not diff:
                TermCourse.objects.create(term=term, course=course)
                return Response({"message": "Course planned successfully"})
            missing_groups.append(diff)

        # Build missing response
        missing_details = [
            Course.objects.filter(id__in=diff).values("id", "course_code", "title")
            for diff in missing_groups
        ]

        return Response({
            "message": "Prerequisites not satisfied",
            "missing": missing_details
        }, status=400)


class RemoveCourseFromTermView(APIView):
    def delete(self, request, student_id, term_id, course_id):
        student = get_object_or_404(Student, pk=student_id)
        term = get_object_or_404(Term, pk=term_id)
        course = get_object_or_404(Course, pk=course_id)

        if term.student != student:
            return Response({"message": "Term does not belong to student"}, status=403)

        TermCourse.objects.filter(term=term, course=course).delete()

        return Response({"message": "Course removed from term"})
