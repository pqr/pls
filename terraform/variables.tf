variable "access_key" {
  default = ""
}

variable "secret_key" {
  default = ""
}

variable "region" {
  default = "eu-central-1"
}

variable "alb_idle_timeout" {
  default = "60"
}

variable "alb_ip_address_type" {
  default = "ipv4"
}

variable "tg_protocol" {
  default = "HTTP"
}

variable "tg_deregistration_delay" {
  default = "300"
}

variable "tg_health_check_port" {
  default = "traffic-port"
}

variable "tg_health_check_protocol" {
  default = "HTTP"
}

variable "tg_health_check_matcher" {
  default = "200"
}

variable "alb_listener_ssl_policy" {
  default = "ELBSecurityPolicy-2016-08"
}
