provider "aws" {
  access_key = "${var.access_key}"
  secret_key = "${var.secret_key}"
  region     = "${var.region}"
}

#VPC
resource "aws_vpc" "main" {
  cidr_block = "172.31.0.0/16"
}

resource "aws_subnet" "1c" {
  vpc_id                  = "${aws_vpc.main.id}"
  cidr_block              = "172.31.0.0/20"
  availability_zone       = "eu-central-1c"
  map_public_ip_on_launch = true
}

resource "aws_subnet" "1b" {
  vpc_id                  = "${aws_vpc.main.id}"
  cidr_block              = "172.31.32.0/20"
  availability_zone       = "eu-central-1b"
  map_public_ip_on_launch = true
}

resource "aws_subnet" "1a" {
  vpc_id                  = "${aws_vpc.main.id}"
  cidr_block              = "172.31.16.0/20"
  availability_zone       = "eu-central-1a"
  map_public_ip_on_launch = true
}

resource "aws_internet_gateway" "main" {
  vpc_id = "${aws_vpc.main.id}"
}

resource "aws_route_table" "main" {
  vpc_id = "${aws_vpc.main.id}"
}

resource "aws_main_route_table_association" "main" {
  vpc_id         = "${aws_vpc.main.id}"
  route_table_id = "${aws_route_table.main.id}"
}

resource "aws_route" "main" {
  route_table_id         = "${aws_route_table.main.id}"
  destination_cidr_block = "0.0.0.0/0"
  gateway_id             = "${aws_internet_gateway.main.id}"
  depends_on             = ["aws_route_table.main"]
}

resource "aws_security_group" "lb" {
  name        = "loadbalancer-sg"
  description = "allow https incoming"
  vpc_id      = "${aws_vpc.main.id}"
}

resource "aws_security_group_rule" "lb" {
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group_rule" "lb-1" {
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  ipv6_cidr_blocks  = ["::/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group_rule" "lb-2" {
  type              = "ingress"
  from_port         = -1
  to_port           = -1
  protocol          = "icmpv6"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group_rule" "lb-3" {
  type              = "ingress"
  from_port         = -1
  to_port           = -1
  protocol          = "icmpv6"
  ipv6_cidr_blocks  = ["::/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group_rule" "lb-4" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group_rule" "lb-5" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  ipv6_cidr_blocks  = ["::/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group_rule" "lb-6" {
  type              = "ingress"
  from_port         = -1
  to_port           = -1
  protocol          = "icmp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group_rule" "lb-7" {
  type              = "ingress"
  from_port         = -1
  to_port           = -1
  protocol          = "icmp"
  ipv6_cidr_blocks  = ["::/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group_rule" "lb-8" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.lb.id}"
}

resource "aws_security_group" "ws" {
  name        = "webserver-sg"
  description = "allow ssh"
  vpc_id      = "${aws_vpc.main.id}"
}

resource "aws_security_group_rule" "ws" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.ws.id}"
}

resource "aws_security_group_rule" "ws-1" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  source_security_group_id = "${aws_security_group.lb.id}"
  security_group_id        = "${aws_security_group.ws.id}"
}

resource "aws_security_group_rule" "ws-2" {
  type                     = "ingress"
  from_port                = 8000
  to_port                  = 8000
  protocol                 = "tcp"
  source_security_group_id = "${aws_security_group.lb.id}"
  security_group_id        = "${aws_security_group.ws.id}"
  description              = "flowerbase http 8000"
}

resource "aws_security_group_rule" "ws-3" {
  type              = "ingress"
  from_port         = 22
  to_port           = 22
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.ws.id}"
}

resource "aws_security_group" "rds" {
  name        = "rds-launch-wizard-1"
  description = "Created from the RDS Management Console"
  vpc_id      = "${aws_vpc.main.id}"
}

resource "aws_security_group_rule" "rds" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.rds.id}"
}

resource "aws_security_group_rule" "rds-1" {
  type                     = "ingress"
  from_port                = 3306
  to_port                  = 3306
  protocol                 = "tcp"
  source_security_group_id = "${aws_security_group.ws.id}"
  security_group_id        = "${aws_security_group.rds.id}"
}

resource "aws_security_group_rule" "rds-2" {
  type                     = "ingress"
  from_port                = -1
  to_port                  = -1
  protocol                 = "icmp"
  source_security_group_id = "${aws_security_group.ws.id}"
  security_group_id        = "${aws_security_group.rds.id}"
}

#EBS and EC2
resource "aws_ebs_volume" "vol-1" {
  availability_zone = "eu-central-1b"
  size              = 10
  type              = "gp2"
}

resource "aws_ebs_volume" "vol-2" {
  availability_zone = "eu-central-1b"
  size              = 60
  type              = "gp2"
}

resource "aws_network_interface" "eni-1" {
  subnet_id       = "${aws_subnet.1b.id}"
  security_groups = ["${aws_security_group.ws.id}"]

  attachment {
    instance     = "${aws_instance.srv-1.id}"
    device_index = 0
  }
}

resource "aws_instance" "srv-1" {
  ami           = "ami-1e339e71"
  instance_type = "t2.micro"
  ebs_optimized = false
  monitoring    = true

  root_block_device {
    volume_type           = "gp2"
    volume_size           = 10
    delete_on_termination = true
  }

  ebs_block_device {
    device_name           = "/dev/sdb"
    volume_type           = "gp2"
    volume_size           = 60
    delete_on_termination = true
  }
}

#LB
# Create ALB
resource "aws_lb" "main" {
  name               = "loadbalancer1"
  internal           = "false"
  load_balancer_type = "application"
  security_groups    = ["${aws_security_group.lb.id}"]
  subnets            = ["${aws_subnet.1a.id}", "${aws_subnet.1b.id}", "${aws_subnet.1c.id}"]
  idle_timeout       = "${var.alb_idle_timeout}"
  ip_address_type    = "${var.alb_ip_address_type}"
  enable_http2       = true
}

# Create default target group
resource "aws_lb_target_group" "flowerbase" {
  name                 = "flowerbase-targetgroup"
  port                 = "8000"
  protocol             = "${var.tg_protocol}"
  vpc_id               = "${aws_vpc.main.id}"
  deregistration_delay = "${var.tg_deregistration_delay}"

  health_check = {
    path                = "/index.php?a=Login"
    port                = "${var.tg_health_check_port}"
    protocol            = "${var.tg_health_check_protocol}"
    matcher             = "${var.tg_health_check_matcher}"
    healthy_threshold   = "5"
    unhealthy_threshold = "2"
  }
}

resource "aws_lb_target_group" "webserver" {
  name                 = "webserver-targetgroup"
  port                 = "80"
  protocol             = "${var.tg_protocol}"
  vpc_id               = "${aws_vpc.main.id}"
  deregistration_delay = "${var.tg_deregistration_delay}"

  health_check = {
    path                = "/?a=Login"
    port                = "${var.tg_health_check_port}"
    protocol            = "${var.tg_health_check_protocol}"
    matcher             = "${var.tg_health_check_matcher}"
    healthy_threshold   = "5"
    unhealthy_threshold = "2"
  }
}

/* This section was comment because instance already attached
# Attach instances to target group
resource "aws_lb_target_group_attachment" "flowerbase" {
  target_group_arn = "${aws_lb_target_group.flowerbase.arn}"
  target_id        = "${aws_instance.srv-1.id}"
}

resource "aws_lb_target_group_attachment" "webserver" {
  target_group_arn = "${aws_lb_target_group.webserver.arn}"
  target_id        = "${aws_instance.srv-1.id}"
}
*/
# Get iam certificate
data "aws_acm_certificate" "main" {
  domain   = "forwardlog.net"
  statuses = ["ISSUED"]
}

# Create load balancer listener
resource "aws_lb_listener" "http" {
  load_balancer_arn = "${aws_lb.main.arn}"
  port              = "80"
  protocol          = "http"

  default_action = {
    target_group_arn = "${aws_lb_target_group.webserver.arn}"
    type             = "forward"
  }
}

resource "aws_lb_listener" "https" {
  load_balancer_arn = "${aws_lb.main.arn}"
  port              = "443"
  protocol          = "https"
  ssl_policy        = "${var.alb_listener_ssl_policy}"
  certificate_arn   = "${data.aws_acm_certificate.main.arn}"

  default_action = {
    target_group_arn = "${aws_lb_target_group.webserver.arn}"
    type             = "forward"
  }
}

resource "aws_lb_listener_rule" "ftp" {
  listener_arn = "${aws_lb_listener.https.arn}"
  priority     = 1

  action {
    type             = "forward"
    target_group_arn = "${aws_lb_target_group.flowerbase.arn}"
  }

  condition {
    field  = "host-header"
    values = ["2.forwardlog.net"]
  }
}

#RDS
resource "aws_rds_cluster" "main" {
  engine                       = "aurora"
  engine_version               = "5.6.10a"
  availability_zones           = ["eu-central-1a", "eu-central-1b", "eu-central-1c"]
  backup_retention_period      = 7
  preferred_backup_window      = "03:05-03:35"
  preferred_maintenance_window = "sun:00:00-sun:03:00"
  cluster_identifier           = "dbcluster1-1"
  skip_final_snapshot          = true
  storage_encrypted            = "false"
  vpc_security_group_ids       = ["${aws_security_group.rds.id}"]
}

resource "aws_rds_cluster_parameter_group" "default" {
  name        = "my-parametergroup"
  family      = "aurora5.6"
  description = "time_zone"

  parameter {
    name  = "time_zone"
    value = "Europe/Helsinki"
  }
}

resource "aws_rds_cluster_instance" "cluster_instances-1" {
  identifier                   = "database1"
  cluster_identifier           = "${aws_rds_cluster.main.id}"
  instance_class               = "db.t2.small"
  engine                       = "aurora"
  engine_version               = "5.6.10a"
  monitoring_interval          = "60"
  promotion_tier               = "1"
  availability_zone            = "eu-central-1a"
  preferred_backup_window      = "03:05-03:35"
  preferred_maintenance_window = "sun:00:00-sun:03:00"
  monitoring_interval          = "60"
  monitoring_role_arn          = "${aws_iam_role.rds_role.arn}"

  tags {
    workload-type = "other"
  }
}

resource "aws_rds_cluster_instance" "cluster_instances-2" {
  identifier                   = "database1-eu-central-1b"
  cluster_identifier           = "${aws_rds_cluster.main.id}"
  instance_class               = "db.t2.small"
  engine                       = "aurora"
  engine_version               = "5.6.10a"
  monitoring_interval          = "60"
  promotion_tier               = "1"
  availability_zone            = "eu-central-1b"
  preferred_backup_window      = "03:05-03:35"
  preferred_maintenance_window = "sun:00:00-sun:03:00"
  monitoring_interval          = "60"
  monitoring_role_arn          = "${aws_iam_role.rds_role.arn}"
}

resource "aws_iam_role" "rds_role" {
  name               = "rds-monitoring-role"
  assume_role_policy = "{\"Version\":\"2012-10-17\",\"Statement\":[{\"Sid\":\"\",\"Effect\":\"Allow\",\"Principal\":{\"Service\":\"monitoring.rds.amazonaws.com\"},\"Action\":\"sts:AssumeRole\"}]}"
}

#Route53
resource "aws_route53_zone" "main" {
  name    = "forwardlog.net."
  comment = "HostedZone created by Route53 Registrar"
}

resource "aws_route53_record" "alb" {
  zone_id = "${aws_route53_zone.main.zone_id}"
  name    = "forwardlog.net."
  type    = "A"

  alias {
    name                   = "${aws_lb.main.dns_name}"
    zone_id                = "${aws_lb.main.zone_id}"
    evaluate_target_health = false
  }
}

resource "aws_route53_record" "alb-1" {
  zone_id = "${aws_route53_zone.main.zone_id}"
  name    = "2.forwardlog.net."
  type    = "A"

  alias {
    name                   = "${aws_lb.main.dns_name}"
    zone_id                = "${aws_lb.main.zone_id}"
    evaluate_target_health = false
  }
}

resource "aws_route53_record" "mx-1" {
  zone_id = "${aws_route53_zone.main.zone_id}"
  name    = "forwardlog.net."
  type    = "MX"
  ttl     = "300"

  records = [
    "10 mx.yandex.net.",
  ]
}

resource "aws_route53_record" "soa-1" {
  zone_id = "${aws_route53_zone.main.zone_id}"
  name    = "forwardlog.net."
  type    = "SOA"
  ttl     = "900"

  records = [
    "ns-1069.awsdns-05.org. awsdns-hostmaster.amazon.com. 1 7200 900 1209600 86400",
  ]
}

resource "aws_route53_record" "cname-1" {
  zone_id = "${aws_route53_zone.main.zone_id}"
  name    = "ftp.forwardlog.net."
  type    = "CNAME"
  ttl     = "300"

  records = [
    "ec2-52-57-97-180.eu-central-1.compute.amazonaws.com",
  ]
}

resource "aws_route53_record" "cname-2" {
  zone_id = "${aws_route53_zone.main.zone_id}"
  name    = "yamail-ac843abc180f.forwardlog.net."
  type    = "CNAME"
  ttl     = "300"

  records = [
    "mail.yandex.ru",
  ]
}

#S3
resource "aws_s3_bucket" "bucket-1" {
  bucket = "pls-backupdb"
  acl    = "private"
  region = "eu-central-1"
}

resource "aws_s3_bucket" "bucket-2" {
  bucket = "pls-backuplog"
  acl    = "private"
  region = "eu-central-1"
}
