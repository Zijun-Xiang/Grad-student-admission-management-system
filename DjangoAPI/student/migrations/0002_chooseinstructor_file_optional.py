from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('student', '0001_choose_instructor'),
    ]

    operations = [
        migrations.AlterField(
            model_name='chooseinstructor',
            name='file',
            field=models.FileField(blank=True, null=True, upload_to='choose_instructor/'),
        ),
    ]
