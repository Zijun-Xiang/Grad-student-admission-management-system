from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = []

    operations = [
        migrations.CreateModel(
            name='ChooseInstructor',
            fields=[
                ('id', models.AutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('facultyId', models.IntegerField()),
                ('facultyName', models.CharField(max_length=255)),
                ('studentId', models.IntegerField()),
                ('studentName', models.CharField(max_length=255)),
                ('file', models.FileField(upload_to='choose_instructor/')),
                ('studentComment', models.TextField(blank=True)),
                ('facultyComment', models.TextField(blank=True)),
                ('state', models.CharField(choices=[('pending', 'pending'), ('approved', 'approved'), ('rejected', 'rejected')], default='pending', max_length=20)),
                ('submittedAt', models.DateTimeField(blank=True, null=True)),
                ('reviewedAt', models.DateTimeField(blank=True, null=True)),
            ],
        ),
    ]
