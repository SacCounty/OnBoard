create table committee_liaisons (
    committee_id int unsigned not null,
    person_id    int unsigned not null,
    primary key (committee_id, person_id),
    foreign key (committee_id) references committees(id),
    foreign key (person_id)    references people(id)
);

alter table people add phone varchar(32) after email;

create table departments (
    id int unsigned not null primary key auto_increment,
    name  varchar(128) not null unique
);
create table committee_departments (
    committee_id  int unsigned not null,
    department_id int unsigned not null,
    primary key (committee_id, department_id),
    foreign key (committee_id)  references committees (id),
    foreign key (department_id) references departments(id)
);

alter table committees add termEndWarningDays  tinyint unsigned not null default 0;
alter table committees add applicationLifetime tinyint unsigned not null default 90;
update committees set applicationLifetime=90;

alter table seats add code varchar(16) after type;
alter table seats modify type enum('termed', 'open') not null default 'termed';

create table media (
	id int unsigned not null primary key auto_increment,
	internalFilename varchar(50)  not null,
	filename         varchar(128) not null,
	mime_type        varchar(128) not null,
	uploaded         datetime     not null,
	applicant_id     int unsigned not null,
	foreign key (applicant_id) references applicants(id)
);
